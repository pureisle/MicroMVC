<?php
/**
 * 单元测试底层
 *
 * 所有需要进行测试的类的父类
 *
 * 调用example:
 *     class xxx extends TestSuite{}
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
use Framework\Models\Log;

class TestSuite {
    /**
     * 单例测试运行接口
     */
    public function run($displayer) {
        $this->_fail_cases = array();
        $this->_pass_cases = array();
        $log_data          = array();
        $class_name        = get_called_class();
        $displayer->normal("$class_name test start running.");
        $log_data['test_class_name'] = $class_name;
        $is_loaded_xdebug            = extension_loaded('xdebug');
        $is_cal_coverage             = $is_loaded_xdebug && defined($class_name . "::TEST_CLASS_NAME") && ! empty($class_name::TEST_CLASS_NAME);
        $log_data['is_cal_coverage'] = $is_cal_coverage;
        if ($is_cal_coverage) {
            $file_path               = ROOT_PATH . "/" . str_replace('\\', '/', $class_name::TEST_CLASS_NAME) . ".php";
            $log_data['target_file'] = $file_path;
            if ( ! file_exists($file_path)) {
                $displayer->fail("[FAILED] test " . $class_name::TEST_CLASS_NAME . " class file not exist");
                return false;
            }
            xdebug_start_code_coverage();
        }
        $log_data['method_test'] = array();
        $this->beginTest();
        foreach (get_class_methods($class_name) as $method) {
            if ($this->_startWith($method, "test")) {
                $displayer->normal("Running $class_name.$method");
                try {
                    $this->SetUp();
                    $log_data['method_test'][$method] = true;
                    $this->$method();
                    $this->CleanUp();
                } catch (Exception $e) {
                    $log_data['method_test'][$method] = false;
                    $displayer->fail("[FAILED] test case $class_name.$method failed");
                    $displayer->fail(nl2br(htmlspecialchars($e->getMessage())));
                    $this->_fail_cases[] = "$class_name.$method";
                    continue;
                }
                $displayer->pass("[PASSED] test case $class_name.$method passed");
                $this->_pass_cases[] = "$class_name.$method";
            }
        }
        $displayer->pass("[RESULT] passed " . count($this->_pass_cases) . "/" . (count($this->_pass_cases) + count($this->_fail_cases)) . " case(s)");
        $this->endTest();
        if ($is_cal_coverage) {
            $run_ret             = xdebug_get_code_coverage();
            $log_data['run_ret'] = $run_ret;
            xdebug_stop_code_coverage();
            $run_ret = $run_ret[$file_path];
            if ( ! empty($run_ret)) {
                $content = file_get_contents($file_path);
                try {
                    $parser     = new PHPFunctionParser($content);
                    $parser_ret = $parser->parse();
                } catch (PHPFunctionParserException $re) {
                    $displayer->pass("[TEST COVERAGE] " . $class_name::TEST_CLASS_NAME . " parser error : " . trim($re->getMessage()));
                    Log::unittestLog($log_data);
                    return count($this->_fail_cases) == 0;
                }
                $log_data['parser_ret'] = $parser_ret;
                if (empty($parser_ret['classes'][$class_name::TEST_CLASS_NAME])) {
                    return count($this->_fail_cases) == 0;
                }
                $parser_ret      = $parser_ret['classes'][$class_name::TEST_CLASS_NAME];
                $line_func_map   = array();
                $line_func_range = array();
                $code_sum_line   = 0;
                foreach ($parser_ret['methods'] as $function_name => $line_set) {
                    $code_sum_line += $line_set[PHPFunctionParser::END_LINE_INDEX] - $line_set[PHPFunctionParser::BEGIN_LINE_INDEX] + 1;
                    $line_func_range[$line_set[PHPFunctionParser::BEGIN_LINE_INDEX]] = $line_set[PHPFunctionParser::END_LINE_INDEX];
                    $line_func_map[$line_set[PHPFunctionParser::END_LINE_INDEX]]     = $function_name;
                }
                $ret = array();
                foreach ($run_ret as $line_num => $value) {
                    foreach ($line_func_range as $k1 => $v1) {
                        if ($line_num >= $k1 && $line_num <= $v1) {
                            $ret[$line_func_map[$v1]]++;
                            break;
                        }
                    }
                }
                foreach ($parser_ret['methods'] as $key => $value) {
                    $sum_line                                = $value[PHPFunctionParser::END_LINE_INDEX] - $value[PHPFunctionParser::BEGIN_LINE_INDEX] + 1 - $value[PHPFunctionParser::INVALID_NUM_INDEX];
                    $parser_ret['methods'][$key]['coverage'] = 100 * $ret[$key] / $sum_line;
                }
                $coverage = round(100 * count($run_ret) / $code_sum_line, 2);
                $displayer->pass("[TEST COVERAGE] class " . $class_name::TEST_CLASS_NAME . " coverage : " . $coverage . " %");
                $method_coverage = count($ret) . "/" . count($parser_ret['methods']);
                $displayer->pass("[TEST COVERAGE] method coverage : " . $method_coverage);
                $log_data['sum_coverage']    = $coverage;
                $log_data['method_coverage'] = $method_coverage;
                Log::unittestLog($log_data);
            } else {
            }
        }
        return count($this->_fail_cases) == 0;
    }
    /**
     * 判断字符串是否具有某前缀
     */
    private function _startWith($str, $prefix) {
        $start_len = strlen($prefix);
        return strlen($str) >= $start_len && substr($str, 0, $start_len) == $prefix;
    }
    /**
     * 单例测试方法前调用，一般覆盖使用
     */
    public function setUp() {}
    /**
     * 单例测试方法后调用，一般覆盖使用
     */
    public function cleanUp() {}
    /**
     * 单例测试开始前调用，一般覆盖使用
     */
    public function beginTest() {}
    /**
     * 单例测试结束后调用，一般覆盖使用
     */
    public function endTest() {}
    /**
     * 获取测试失败的例子
     */
    public function failCases() {
        return $this->_fail_cases;
    }
    /**
     * 获取通过测试的例子
     */
    public function passCases() {
        return $this->_pass_cases;
    }
    /**
     * 断言match
     *
     * @param string $expect期望值的preg_match正则表达式
     * @param string $real待测试值
     * @param string $ext_msg=null
     */
    protected function assertMatch($expect, $real, $ext_msg = null) {
        if (preg_match($expect, $real, $tmp) === 0) {
            $msg = 'assertMatch Failed :Expect regular [' . $expect . '] which really string is [' . $real . '], not match.';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言数值或数值字符串
     *
     * @param string $expect期望值的preg_match正则表达式
     * @param string $real待测试值
     * @param string $ext_msg=null
     */
    protected function assertNum($real, $ext_msg = null) {
        if ( ! is_numeric($real)) {
            $msg = 'assertMatch Failed :Expect numeric which really is [' . $real . '], is not num.';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言数浮点型数字
     *
     * @param string $expect期望值的preg_match正则表达式
     * @param string $real待测试值
     * @param string $ext_msg=null
     */
    protected function assertFloat($real, $ext_msg = null) {
        if ( ! is_float($real)) {
            $msg = 'assertMatch Failed :Expect float which really is [' . $real . '], is not num.';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 检查对象类型
     * @param string $expect
     * @param object $real
     * @param string $ext_msg
     */
    protected function assertTypeOf($expect, $real, $ext_msg = null) {
        if ($real instanceof $expect !== true) {
            $msg = 'assertTypeOf Failed :Expect ' . $expect . ' which really is [' . get_class($real) . '].';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言equal
     *
     * @param mix    $expect期望值
     * @param mix    $real待测试值
     * @param string $ext_msg=null
     */
    protected function assertEq($expect, $real, $ext_msg = null) {
        if ($expect !== $real) {
            $msg = 'assertEq Failed :Expect [' . $this->_var2str($expect) . '] which really is [' . $this->_var2str($real) . '].';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言not equal
     *
     * @param mix    $expect期望值
     * @param mix    $real待测试值
     * @param string $ext_msg=null
     */
    protected function assertNe($expect, $real, $ext_msg = null) {
        if ($expect === $real) {
            $msg = 'assertNe Failed :Expect [' . $this->_var2str($expect) . '] not equal to [$' . $this->_var2str($real) . '], but they do equal.';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言true
     *
     * @param bool   $val
     * @param string $ext_msg=null
     */
    protected function assertTrue($val, $ext_msg = null) {
        if (true !== $val) {
            $msg = 'assertTrue Failed : [' . $val . '] is not true';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    /**
     * 断言false
     *
     * @param bool   $val
     * @param string $ext_msg=null
     */
    protected function assertFalse($val, $ext_msg = null) {
        if (false !== $val) {
            $msg = 'assertFalse Failed :[' . $val . '] is true';
            if (null != $ext_msg) {
                $msg .= "With Ext:" . $ext_msg;
            }
            $this->_throwExcption($msg);
        }
    }
    private function _var2str($var) {
        return json_encode($var);
    }
    /**
     * 抛出异常
     */
    private function _throwExcption($msg = null) {
        throw new TestSuiteException($msg);
    }
    private $_fail_cases;
    private $_pass_cases;
}

/**
 * 配置文件解析异常类
 */
class TestSuiteException extends Exception {
    const ERROR_ASSERT = 1;
    public $ERROR_SET  = array(
        self::ERROR_ASSERT => array(
            'code'    => self::ERROR_ASSERT,
            'message' => 'assert fail!'
        )
    );
    public function __construct($msg) {
        $this->ERROR_SET[self::ERROR_ASSERT]['message'] = $msg;
        parent::__construct(self::ERROR_ASSERT);
    }
}