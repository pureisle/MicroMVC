<?php
/**
 * PHP 代码解析
 *
 * 使用方法：
 * $ps = new  PHPFunctionParser($source_string);
 * $ret = $ps->parse();
 *
 * @author zhiyuan12 <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class PHPFunctionParser {
    const BEGIN_LINE_INDEX       = 'begin_line';
    const END_LINE_INDEX         = 'end_line';
    const NAMESPACE_INDEX        = 'current_namespace';
    const CURRENT_CLASS_INDEX    = 'current_class';
    const CURRENT_FUNCTION_INDEX = 'current_function';
    private $_content            = '';
    private $_tokens             = array();
    public function __construct($contents) {
        $this->_content = $contents;
    }
    public function parse() {
        $this->_tokens = token_get_all($this->_content);
        $sm            = new FiniteStateMachine();                                 //初始化状态机
        $sm->setTickData($this->_tokens);                                          //设置时钟数据
        $sm->register(Init::STATE, new Init($sm, InPHP::STATE));                   //只能进入php代码一种状态
        $sm->register(InPHP::STATE, new InPHP($sm));                               //可能遇到类、接口、函数或者退出php,需要状态自己判断
        $sm->register(MeetFunc::STATE, new MeetFunc($sm, InFunc::STATE));          //遇到函数后只能进入函数
        $sm->register(InFunc::STATE, new InFunc($sm, InPHP::STATE));               //在函数里结束后只能进入php
        $sm->register(MeetClass::STATE, new MeetClass($sm, InClass::STATE));       //遇到类后只能进入class
        $sm->register(InClass::STATE, new InClass($sm));                           //在类内可以结束类或进入成员方法
        $sm->register(MeetMethod::STATE, new MeetMethod($sm, InMethod::STATE));    //遇到成员方法后只能进入方法
        $sm->register(InMethod::STATE, new InMethod($sm, InClass::STATE));         //成员方法结束后只能退出到类内
        $sm->register(MeetNameSpace::STATE, new MeetNameSpace($sm, InPHP::STATE)); //遇到命名空间解析后只能返回php
        $sm->setInitState(Init::STATE);                                            //起始状态在php代码外
        $sm->run();                                                                //运行状态机
        return $sm->getData();
    }
}

class PHPFunctionParserException extends Exception {
}

class Init extends FiniteState {
    const STATE = 0; // 初始状态，在php代码之外
    public function onStateEnter() {}
    public function onStateTick($token = null) {
        if (is_array($token) && T_OPEN_TAG === $token[0]) {
            return true;
        }
        return false;
    }
    public function onStateExit() {}
}
class InPHP extends FiniteState {
    const STATE = 1; // 进入php代码
    public function onStateEnter() {}
    public function onStateTick($token = null) {
        $pattern = is_array($token) ? $token[0] : $token;
        switch ($pattern) {
            case T_CLASS:
                $this->trans(MeetClass::STATE);
                break;
            case T_INTERFACE:
                break;
            case T_FUNCTION:
                $this->trans(MeetFunc::STATE);
                break;
            case T_NAMESPACE:
                $this->trans(MeetNameSpace::STATE);
                break;
            case T_STRING:
                break;
            case '{':
            case '}':
                break;
            case T_WHITESPACE:
            default:
                # code...
                break;
        }
        return false;
    }
    public function onStateExit() {}
}
class MeetFunc extends FiniteState {
    private $_name;
    private $_begin_line;
    const STATE = 2; // 碰到了函数定义，但还没进到函数里面
    public function onStateEnter() {
        $this->_name       = '';
        $this->_begin_line = 0;
    }
    public function onStateTick($token = null) {
        if (is_string($token) && '{' === $token) {
            $data = $this->getData();
            $f_n  = trim($this->_name);
            if (isset($data[PHPFunctionParser::NAMESPACE_INDEX])) {
                $f_n = $data[PHPFunctionParser::NAMESPACE_INDEX] . "\\" . $f_n;
            }
            $data['functions'][$f_n]                         = array(PHPFunctionParser::BEGIN_LINE_INDEX => $this->_begin_line);
            $data[PHPFunctionParser::CURRENT_FUNCTION_INDEX] = $f_n;
            $this->setData($data);
            return true;
        }
        if (is_array($token) && 0 === $this->_begin_line) {
            $this->_begin_line = $token[2];
        }
        $this->_name .= is_string($token) ? $token : $token[1];
    }
    public function onStateExit() {}
}
class InFunc extends FiniteState {
    private $_left_braces;
    private $_last_line_num;
    const STATE = 3; // 在函数里面了
    public function onStateEnter() {
        $this->_left_braces   = 1;
        $this->_last_line_num = 0;
    }
    public function onStateTick($token = null) {
        update_line_num($this->_last_line_num, $token);
        if (is_string($token)) {
            if ('{' === $token) {
                $this->_left_braces++;
            } else if ('}' === $token) {
                $this->_left_braces--;
            }
            if (0 === $this->_left_braces) {
                $data                                                                                                   = $this->getData();
                $data['functions'][$data[PHPFunctionParser::CURRENT_FUNCTION_INDEX]][PHPFunctionParser::END_LINE_INDEX] = $this->_last_line_num;
                unset($data[PHPFunctionParser::CURRENT_FUNCTION_INDEX]);
                $this->setData($data);
                $this->trans(InPHP::STATE);
            }
        }
    }
    public function onStateExit() {}
}
class MeetClass extends FiniteState {
    private $_stop_join_name = false;
    private $_name;
    private $_begin_line;
    const STATE = 4; // 碰到了类定义，但还没进到类里面
    public function onStateEnter() {
        $this->_name       = '';
        $this->_begin_line = 0;
    }
    public function onStateTick($token = null) {
        if (is_string($token) && '{' === $token) {
            $data = $this->getData();
            $c_n  = trim($this->_name);
            if (isset($data[PHPFunctionParser::NAMESPACE_INDEX])) {
                $c_n = $data[PHPFunctionParser::NAMESPACE_INDEX] . "\\" . $c_n;
            }
            $data['classes'][$c_n]                        = array(PHPFunctionParser::BEGIN_LINE_INDEX => $this->_begin_line);
            $data[PHPFunctionParser::CURRENT_CLASS_INDEX] = $c_n;
            $this->setData($data);
            return true;
        } else if (is_array($token)) {
            if (0 === $this->_begin_line) {
                $this->_begin_line = $token[2];
            }
            if (T_EXTENDS === $token[0] || T_IMPLEMENTS === $token[0]) {
                $this->_stop_join_name = true;
            }
        }
        if ( ! $this->_stop_join_name) {
            $this->_name .= is_string($token) ? $token : $token[1];
        }
    }
    public function onStateExit() {}
}
class InClass extends FiniteState {
    private $_last_line_num;
    const STATE = 5; // 在类里面了
    public function onStateEnter() {}
    public function onStateTick($token = null) {
        update_line_num($this->_last_line_num, $token);
        if (is_string($token) && '}' === $token) {
            $data                                                                                              = $this->getData();
            $data['classes'][$data[PHPFunctionParser::CURRENT_CLASS_INDEX]][PHPFunctionParser::END_LINE_INDEX] = $this->_last_line_num;
            unset($data[PHPFunctionParser::CURRENT_CLASS_INDEX]);
            $this->setData($data);
            $this->trans(InPHP::STATE);
        }
        if (T_FUNCTION === $token[0]) {
            $this->trans(MeetMethod::STATE);
        }
    }
    public function onStateExit() {}
}
class MeetMethod extends FiniteState {
    private $_name;
    private $_begin_line;
    const STATE = 6; // 碰到了类中方法的定义，但还没进到方法里面
    public function onStateEnter() {
        $this->_name       = '';
        $this->_begin_line = 0;
    }
    public function onStateTick($token = null) {
        if (is_string($token) && '{' === $token) {
            $data                                                                            = $this->getData();
            $f_n                                                                             = trim($this->_name);
            $data['classes'][$data[PHPFunctionParser::CURRENT_CLASS_INDEX]]['methods'][$f_n] = array(PHPFunctionParser::BEGIN_LINE_INDEX => $this->_begin_line);
            $data[PHPFunctionParser::CURRENT_FUNCTION_INDEX]                                 = $f_n;
            $this->setData($data);
            return true;
        }
        if (is_array($token) && 0 === $this->_begin_line) {
            $this->_begin_line = $token[2];
        }
        $this->_name .= is_string($token) ? $token : $token[1];
    }
    public function onStateExit() {}
}
class InMethod extends FiniteState {
    private $_left_braces;
    private $_last_line_num;
    const STATE = 7; // 进到类的方法里面了
    public function onStateEnter() {
        $this->_left_braces   = 1;
        $this->_last_line_num = 0;
    }
    public function onStateTick($token = null) {
        update_line_num($this->_last_line_num, $token);
        if (is_string($token)) {
            if ('{' === $token) {
                $this->_left_braces++;
            } else if ('}' === $token) {
                $this->_left_braces--;
            }
            if (0 === $this->_left_braces) {
                $data                                                                                                                                                           = $this->getData();
                $data['classes'][$data[PHPFunctionParser::CURRENT_CLASS_INDEX]]['methods'][$data[PHPFunctionParser::CURRENT_FUNCTION_INDEX]][PHPFunctionParser::END_LINE_INDEX] = $this->_last_line_num;
                unset($data[PHPFunctionParser::CURRENT_FUNCTION_INDEX]);
                $this->setData($data);
                $this->trans(InClass::STATE);
            }
        }
    }
    public function onStateExit() {}
}
class MeetNameSpace extends FiniteState {
    private $_name;
    const STATE = 8;
    public function onStateEnter() {
        $this->_name = '';
    }
    public function onStateTick($token = null) {
        if (is_string($token) && ';' === $token) {
            $data                                     = $this->getData();
            $data[PHPFunctionParser::NAMESPACE_INDEX] = trim($this->_name);
            $this->setData($data);
            return true;
        }
        $this->_name .= is_string($token) ? $token : $token[1];
    }
    public function onStateExit() {}
}
function update_line_num(&$line_num, $token) {
    if (is_array($token)) {
        $line_num = $token[2];
        if (T_WHITESPACE === $token[0]) {
            $count1 = substr_count($token[1], "\r");
            $count2 = substr_count($token[1], "\n");
            $line_num += max($count1, $count2);
        }
    }
}