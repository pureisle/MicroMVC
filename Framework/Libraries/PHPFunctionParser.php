<?php
/**
 * Function Parser for PHP file. 给定一个php文件，输出该文件内定义的函数和行号范围
 *
 * @version 1.0
 * @author Horst Xu (271021733@qq.com)
 */
namespace Framework\Libraries;
class PHPFunctionParser {
    const STATE_INIT        = 1; //初始状态，在函数之外，类之外
    const STATE_MEET_FUNC   = 2; //碰到了函数定义，但还没进到函数里面
    const STATE_IN_FUNC     = 3; //在函数里面了
    const STATE_MEET_CLASS  = 4; //碰到了类定义，但还没进到类里面
    const STATE_IN_CLASS    = 5; //在类里面了
    const STATE_MEET_METHOD = 6; //碰到了类中方法的定义，但还没进到方法里面
    const STATE_IN_METHOD   = 7; //进到类的方法里面了
    private $tokens;
    private $contents;
    private $in_php; //当前是在php代码里面
    private $state;
    private $function_list;
    private $class_name;
    private $function_name;
    private $start_line;
    private $end_line;
    private $last_line; //当前行的行号
    private $left_brace_cnt;
    private $meet_to_in_func_method; //从Meet到In状态转变时的子状态
    private $prev_token;             //前面一个token，主要用于判断当前行号
    public function __construct(&$_contents) {
        $this->contents                   = &$_contents;
        $this->in_php                     = false;
        $this->state                      = self::STATE_INIT;
        $this->function_list              = array();
        $this->class_name                 = "";
        $this->function_name              = "";
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->last_line                  = 0;
        $this->left_func_method_brace_cnt = 0; //进入函数或方法内部时统计括号数量
        $this->left_class_brace_cnt       = 0; //进入到类内部时统计括号数量
        $this->left_brace_cnt             = 0; //总的括号统计数量
        $this->meet_to_in_func_method     = 0; //"t_function => 1"; "t_string => 2";"( => 3"; ") => 4"; "{ => 5"; "} => 0"
        $this->prev_token                 = false;
    }
    private function update_last_line($token) {
        if (is_array($token)) {
            $this->last_line = $token[2]; //记录当前行的行号
            return;
        }
        // is_string
        if (is_array($this->prev_token)) {
            if (T_WHITESPACE == $this->prev_token[0]) {
                $count1          = substr_count($this->prev_token[1], "\r");
                $count2          = substr_count($this->prev_token[1], "\n");
                $this->last_line = $this->prev_token[2] + max($count1, $count2);
            } else {
                $this->last_line = $this->prev_token[2];
            }
        }
    }
    public function process() {
        $this->tokens = token_get_all($this->contents);
        foreach ($this->tokens as &$token) {
            $this->update_last_line($token);
            switch ($this->state) {
                case self::STATE_INIT:
                    if ($this->should_change_init_to_meet_func($token)) {
                        $this->change_init_to_meet_func($token);
                    } else if ($this->should_change_init_to_meet_class($token)) {
                        $this->change_init_to_meet_class($token);
                    } else {
                        $this->update_init_info($token);
                    }
                    break;
                case self::STATE_MEET_FUNC:
                    if ($this->should_change_meet_func_to_init($token)) {
                        $this->change_meet_func_to_init($token);
                    } else if ($this->should_change_meet_func_to_in_func($token)) {
                        $this->change_meet_func_to_in_func($token);
                    } else {
                        $this->update_meet_func_info($token);
                    }
                    break;
                case self::STATE_IN_FUNC:
                    if ($this->should_change_in_func_to_init($token)) {
                        $this->change_in_func_to_init($token);
                    } else {
                        $this->update_in_func_info($token);
                    }
                    break;
                case self::STATE_MEET_CLASS:
                    if ($this->should_change_meet_class_to_init($token)) {
                        $this->change_meet_class_to_init($token);
                    } else if ($this->should_change_meet_class_to_in_class($token)) {
                        $this->change_meet_class_to_in_class($token);
                    } else {
                        $this->update_meet_class_info($token);
                    }
                    break;
                case self::STATE_IN_CLASS:
                    if ($this->should_change_in_class_to_init($token)) {
                        $this->change_in_class_to_init($token);
                    } else if ($this->should_change_in_class_to_meet_method($token)) {
                        $this->change_in_class_to_meet_method($token);
                    } else {
                        $this->update_in_class_info($token);
                    }
                    break;
                case self::STATE_MEET_METHOD:
                    if ($this->should_change_meet_method_to_in_class($token)) {
                        $this->change_meet_method_to_in_class($token);
                    } else if ($this->should_change_meet_method_to_in_method($token)) {
                        $this->change_meet_method_to_in_method($token);
                    } else {
                        $this->update_meet_method_info($token);
                    }
                    break;
                case self::STATE_IN_METHOD:
                    if ($this->should_change_in_method_to_in_class($token)) {
                        $this->change_in_method_to_in_class($token);
                    } else {
                        $this->update_in_method_info($token);
                    }
                    break;
                default:
                    break;
            }
            $this->prev_token = $token;
        }
        if (0 != $this->left_class_brace_cnt || 0 != $this->left_brace_cnt || 0 != $this->left_func_method_brace_cnt) {
            throw new RuntimeException($this->error_message("Brace Mismatch!"));
        }
        return $this->function_list;
    }
    private function should_change_in_method_to_in_class($token) {
        if ( ! $this->in_php || self::STATE_IN_METHOD != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && "}" == $token && 1 == $this->left_func_method_brace_cnt &&
            $this->left_class_brace_cnt > 0) {
            return true;
        }
        return false;
    }
    private function change_in_method_to_in_class($token) {
        if (self::STATE_IN_METHOD != $this->state) {
            return;
        }
        $this->state    = self::STATE_IN_CLASS;
        $this->end_line = $this->last_line;
        $this->record_information(); //记录当前的数据
        $this->function_name              = "";
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->left_func_method_brace_cnt = 0; //func内部括号数目归零
        $this->left_class_brace_cnt--;         //类内部括号数减一
        $this->left_brace_cnt--;               //总计括号数减一
        $this->meet_to_in_func_method = 0;     //子状态归零
    }
    private function update_in_method_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    $this->left_func_method_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    $this->left_func_method_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    $this->left_func_method_brace_cnt--;
                    $this->left_class_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function update_meet_method_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case T_STRING:
                    //函数名称
                    $this->function_name = $token[1];
                    if (1 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 2;
                    }
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    throw new RuntimeException($this->error_message($token));
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case "(":
                    if (2 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 3;
                    }
                    break;
                case ")":
                    if (3 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 4;
                    }
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    $this->left_class_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function change_meet_method_to_in_class($token) {
        if (self::STATE_MEET_METHOD != $this->state) {
            return;
        }
        $this->state = self::STATE_IN_CLASS;
        if ("{" == $token) {
            $this->left_brace_cnt++;
            $this->left_class_brace_cnt++;
        } else if ("}" == $token) {
            $this->left_brace_cnt--;
            $this->left_class_brace_cnt--;
        }
        $this->left_func_method_brace_cnt = 0;
        $this->meet_to_in_func_method     = 0;
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->function_name              = "";
    }
    private function should_change_meet_method_to_in_class($token) {
        if ( ! $this->in_php || self::STATE_MEET_METHOD != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && ";" == $token) {
            return true;
        }
        if (is_string($token) && ("(" == $token || ")" == $token || "{" == $token || "}" == $token) && "" == $this->function_name) {
            return true;
        }
        return false;
    }
    private function change_meet_method_to_in_method($token) {
        if (self::STATE_MEET_METHOD != $this->state) {
            return;
        }
        $this->state                  = self::STATE_IN_METHOD;
        $this->meet_to_in_func_method = 5;
        $this->left_brace_cnt++;
        $this->left_class_brace_cnt++;
        $this->left_func_method_brace_cnt++;
    }
    private function should_change_meet_method_to_in_method($token) {
        if ( ! $this->in_php || self::STATE_MEET_METHOD != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && $token = "{" && 4 == $this->meet_to_in_func_method) {
            return true;
        }
        return false;
    }
    private function update_in_class_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    $this->left_class_brace_cnt++;
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    $this->left_class_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function change_in_class_to_init($token) {
        if (self::STATE_IN_CLASS != $this->state) {
            return;
        }
        $this->state = self::STATE_INIT;
        $this->left_brace_cnt--;
        $this->left_class_brace_cnt--;
        $this->class_name    = "";
        $this->function_name = "";
        $this->start_line    = -1;
        $this->end_line      = -1;
    }
    private function should_change_in_class_to_init($token) {
        if ( ! $this->in_php || self::STATE_IN_CLASS != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && "}" == $token && 1 == $this->left_class_brace_cnt && 0 == $this->left_func_method_brace_cnt && 1 == $this->left_brace_cnt) {
            return true;
        }
        return false;
    }
    private function change_in_class_to_meet_method($token) {
        if (self::STATE_IN_CLASS != $this->state) {
            return;
        }
        $this->state                  = self::STATE_MEET_METHOD;
        $this->start_line             = $this->last_line;
        $this->meet_to_in_func_method = 1;
    }
    private function should_change_in_class_to_meet_method($token) {
        if ( ! $this->in_php || self::STATE_IN_CLASS != $this->state || ! is_array($token) || T_FUNCTION != $token[0]) {
            return false;
        }
        if ("" == $this->class_name || $this->left_class_brace_cnt < 1 || 0 != $this->left_func_method_brace_cnt) {
            return false;
        }
        return true;
    }
    private function update_meet_class_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    break;
                case T_STRING:
                    $this->class_name = $token[1];
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function should_change_meet_class_to_in_class($token) {
        if ( ! $this->in_php || self::STATE_MEET_CLASS != $this->state || is_array($token)) {
            return false;
        }
        if ("" != $this->class_name && "{" == $token) {
            return true;
        }
        return false;
    }
    private function change_meet_class_to_in_class($token) {
        if (self::STATE_MEET_CLASS != $this->state) {
            return;
        }
        $this->state = self::STATE_IN_CLASS;
        $this->left_brace_cnt++;       //全局左括号数量
        $this->left_class_brace_cnt++; //类中的左括号数量
    }
    private function should_change_meet_class_to_init($token) {
        if ( ! $this->in_php || self::STATE_MEET_CLASS != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && ";" == $token) {
            //碰到分号
            return true;
        }
        if (is_string($token) && ("(" == $token || ")" == $token || "{" == $token || "}" == $token) && "" == $this->class_name) {
            return true;
        }
        return false;
    }
    private function change_meet_class_to_init($token) {
        if (self::STATE_MEET_CLASS != $this->state) {
            return;
        }
        if ("{" == $token) {
            $this->left_brace_cnt++;
        } else if ("}" == $token) {
            $this->left_brace_cnt--;
        }
        $this->state                      = self::STATE_INIT;
        $this->class_name                 = "";
        $this->function_name              = "";
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->left_func_method_brace_cnt = 0;
        $this->meet_to_in_func_method     = 0;
    }
    private function update_in_func_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    $this->left_func_method_brace_cnt++;
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    $this->left_func_method_brace_cnt++;
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    $this->left_func_method_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function change_in_func_to_init($token) {
        if (self::STATE_IN_FUNC != $this->state) {
            return;
        }
        $this->end_line = $this->last_line;
        $this->record_information(); //记录当前的数据
        $this->state                      = self::STATE_INIT;
        $this->function_name              = "";
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->left_func_method_brace_cnt = 0; //func内部括号数目归零
        $this->left_brace_cnt--;
        $this->meet_to_in_func_method = 0; //子状态归零
    }
    private function should_change_in_func_to_init($token) {
        if ( ! $this->in_php || self::STATE_IN_FUNC != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && "}" == $token && 1 == $this->left_func_method_brace_cnt && 0 == $this->left_class_brace_cnt) {
            return true;
        }
        return false;
    }
    private function should_change_init_to_meet_func($token) {
        if ( ! $this->in_php || self::STATE_INIT != $this->state || ! is_array($token) || T_FUNCTION != $token[0]) {
            return false;
        }
        if (0 != $this->left_class_brace_cnt || 0 != $this->left_func_method_brace_cnt) {
            return false;
        }
        return true;
    }
    private function change_init_to_meet_func($token) {
        if (self::STATE_INIT != $this->state) {
            return;
        }
        $this->state                  = self::STATE_MEET_FUNC;
        $this->start_line             = $this->last_line;
        $this->meet_to_in_func_method = 1;
    }
    private function should_change_init_to_meet_class($token) {
        if ( ! $this->in_php || self::STATE_INIT != $this->state || ! is_array($token) || T_CLASS != $token[0]) {
            return false;
        }
        if (0 != $this->left_class_brace_cnt || 0 != $this->left_func_method_brace_cnt) {
            return false;
        }
        return true;
    }
    private function change_init_to_meet_class($token) {
        if (self::STATE_INIT != $this->state) {
            return;
        }
        $this->state = self::STATE_MEET_CLASS;
    }
    private function update_init_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function should_change_meet_func_to_init($token) {
        if ( ! $this->in_php || self::STATE_MEET_FUNC != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && ";" == $token) {
            return true;
        }
        if (is_string($token) && ("(" == $token || ")" == $token || "{" == $token || "}" == $token) && "" == $this->function_name) {
            return true;
        }
        return false;
    }
    private function change_meet_func_to_init($token) {
        if (self::STATE_MEET_FUNC != $this->state) {
            return;
        }
        $this->state = self::STATE_INIT;
        if ("{" == $token) {
            $this->left_brace_cnt++;
        } else if ("}" == $token) {
            $this->left_brace_cnt--;
        }
        $this->left_func_method_brace_cnt = 0;
        $this->meet_to_in_func_method     = 0;
        $this->start_line                 = -1;
        $this->end_line                   = -1;
        $this->function_name              = "";
    }
    private function should_change_meet_func_to_in_func($token) {
        if ( ! $this->in_php || self::STATE_MEET_FUNC != $this->state || is_array($token)) {
            return false;
        }
        if (is_string($token) && $token = "{" && 4 == $this->meet_to_in_func_method) {
            return true;
        }
        return false;
    }
    private function change_meet_func_to_in_func($token) {
        if (self::STATE_MEET_FUNC != $this->state) {
            return;
        }
        $this->state                  = self::STATE_IN_FUNC;
        $this->meet_to_in_func_method = 5;
        $this->left_brace_cnt++;
        $this->left_func_method_brace_cnt++;
    }
    private function update_meet_func_info($token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $this->in_php = true;
                    break;
                case T_CLOSE_TAG:
                    $this->in_php = false;
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_STRING_VARNAME:
                    $this->left_brace_cnt++;
                    break;
                case T_STRING:
                    //函数名称
                    $this->function_name = $token[1];
                    if (1 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 2;
                    }
                    break;
                case T_FUNCTION:
                case T_CLASS:
                    throw new RuntimeException($this->error_message($token));
                    break;
                default:
                    break;
            }
        } else {
            switch ($token) {
                case "{":
                    $this->left_brace_cnt++;
                    break;
                case "(":
                    if (2 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 3;
                    }
                    break;
                case ")":
                    if (3 == $this->meet_to_in_func_method) {
                        $this->meet_to_in_func_method = 4;
                    }
                    break;
                case "}":
                    $this->left_brace_cnt--;
                    break;
                default:
                    break;
            }
        }
    }
    private function record_information() {
        if ("" != $this->function_name) {
            if ("" == $this->class_name && $this->start_line > 0 && $this->end_line > 0) {
                $this->function_list[$this->function_name] = array($this->start_line, $this->end_line);
            } else if ("" != $this->class_name && $this->start_line > 0 && $this->end_line > 0) {
                $full_name                       = $this->class_name . "::" . $this->function_name;
                $this->function_list[$full_name] = array($this->start_line, $this->end_line);
            }
        }
        $this->function_name = "";
        $this->start_line    = -1;
        $this->end_line      = -1;
    }
    private function error_message($token) {
        if (is_array($token)) {
            return "Error occurs!\n" .
            "Token name: " . token_name($token[0]) . "\n" .
                "Source code line: " . $token[2] . "\n" .
                "Source code: \n" .
                "########################" .
                $token[1] .
                "########################" . "\n";
        }
        if (is_string($token)) {
            return "Error occurs!\n" .
                "Error Infomation (Position): " . $token . "\n";
        }
        return false;
    }
}