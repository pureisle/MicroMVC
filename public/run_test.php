<?php
/**
 * 执行测试方法
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
$env = array(
    'main'   => 'main',
    'module' => 'Demo'
);
require "cli.php";

//入口函数
use Framework\Libraries\UnitTest;
/**
 * 入口执行函数
 *
 * php public/run_test.php Framework TestPDOManager.php
 *
 * @param    array $config
 * @param    array $argv     // 1=>module ; 2=>file_name
 * @return
 */
function main($config, $argv) {
    if ( ! empty($argv[1])) {
        $modules = array($argv[1] => true);
        if ( ! empty($argv[2])) {
            $test_file = $argv[2];
            UnitTest::includeTestFile(ROOT_PATH . '/' . $argv[1] . '/Tests/' . $test_file);
            UnitTest::run();
            return true;
        }
    } else {
        $modules = $config['modules'];
    }
    UnitTest::includeTestFile(FRAMEWORK_PATH . '/Tests');
    foreach ($modules as $module => $is_ok) {
        if ( ! $is_ok) {
            continue;
        }
        UnitTest::includeTestFile(ROOT_PATH . '/' . $module . '/Tests');
    }
    UnitTest::run();
}

    /**
     * 主辅库分离
     * @param  string   $sql
     * @return string
     */
    // private function _DBChoice($sql) {
    //     $sql_components = explode(' ', ltrim($sql), 2);
    //     $verb           = strtolower($sql_components[0]);
    //     $db_type        = 'slave';
    //     switch ($verb) {
    //         case "select":
    //         case "describe":
    //         case "show":
    //             $db_type = 'slave';
    //             break;
    //         case "delete":
    //         case "update":
    //         case "truncate":
    //         case "replace":
    //         case "rename":
    //         case "alter":
    //         case "drop":
    //         case "create":
    //         case "insert":
    //             $db_type = 'master';
    //             break;
    //         default:
    //             throw new PDOManagerException(PDOManagerException::ERROR_SQL_SYNTAX);
    //             return false;
    //     }
    //     return $db_type;
    // }