<?php
/**
 * 单元测试底层
 *
 * 对调用页面的所有TestSuite子类进行调用并统计相关数据
 *
 * 调用example:
 *     UnitTest::run ();
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class UnitTest {
    /**
     * 静态方法，调用后执行调用页所有TestSuite子对象
     *
     * @param object $displayer
     */
    public static function run($displayer = null) {
        if (null == $displayer) {
            if (php_sapi_name() == 'cli') {
                $displayer = new CLIDisplayer();
            } else {
                $displayer = new HTMLDisplayer();
            }
        }
        $passed_suite_num  = 0;
        $total_suite_num   = 0;
        $fail_cases        = array();
        $passed_case_count = 0;
        $total_case_count  = 0;
        foreach (get_declared_classes() as $class) {
            // var_dump($class);
            if (is_subclass_of($class, "Framework\Libraries\TestSuite")) {
                ++$total_suite_num;
                $test_case = new $class();
                if ( ! $test_case->run($displayer)) {
                    $fail_cases = array_merge($fail_cases, $test_case->failCases());
                } else {
                    ++$passed_suite_num;
                }
                $passed_case_count += count($test_case->passCases());
                $total_case_count += count($test_case->passCases()) + count($test_case->failCases());
            }
        }
        if ($total_case_count == $passed_case_count) {
            $displayer->pass("[PASSED] run all test suite passed");
        } else {
            $displayer->fail("[FAILED] passed " . $passed_suite_num / $total_suite_num . " suite");
            $displayer->fail("[FAILED] passed " . $passed_case_count / $total_case_count . " case(s)");
            $displayer->normal("Failed Cases:");
            foreach ($fail_cases as $case) {
                $displayer->fail($case);
            }
        }
    }
    /**
     * 加载指定路径下的所有php文件
     * @param  string    $path
     * @return boolean
     */
    public static function includeTestFile($path) {
        if (empty($path)) {
            $path = './';
        }
        if (is_file($path)) {
            include_once $path;
            return true;
        }
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ('.' == $file || '..' == $file) {
                    continue;
                }
                $tmp_path = $path . "/" . $file;
                if (is_dir($tmp_path)) {
                    return self::includeTestFile($tmp_path);
                }
                $tmp = pathinfo($file);
                if ('php' != $tmp['extension']) {
                    continue;
                }
                include_once $path . DIRECTORY_SEPARATOR . $file;
            }
            closedir($handle);
        }
        return true;
    }
}
/**
 * 输出模板类
 */
abstract class Displayer {
    abstract public function pass($msg);
    abstract public function fail($msg);
    abstract public function normal($msg);
}
/**
 * html输出信息格式类
 */
class HTMLDisplayer extends Displayer {
    public function pass($msg) {
        echo "<p style=\"color:green\">" . $msg . "</p>\n";
    }
    public function fail($msg) {
        echo "<p style=\"color:red\">" . $msg . "</p>\n";
    }
    public function normal($msg) {
        echo "<p>" . $msg . "</p>";
    }
}
/**
 * cli输出格式
 */
class CLIDisplayer extends Displayer {
    public function pass($msg) {
        $this->_echo($msg);
    }
    public function fail($msg) {
        $this->_echo($msg);
    }
    public function normal($msg) {
        $this->_echo($msg);
    }
    private function _echo($msg) {
        echo microtime() . "\t" . $msg . "\n";
    }
}
