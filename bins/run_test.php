#!/usr/bin/env php
<?php
/**
 * 执行测试方法
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
if ( ! empty($argv[1])) {
    $module = $argv[1];
} else {
    $module = 'Framework';
}

$env = array(
    'module' => $module
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
        //默认配置module增加框架测试
        UnitTest::includeTestFile(FRAMEWORK_PATH . '/Tests');
    }
    foreach ($modules as $module => $is_ok) {
        if ( ! $is_ok) {
            continue;
        }
        //注册多module的自动加载
        $autoload = new Framework\Models\AutoLoad($module);
        $autoload->register();
        UnitTest::includeTestFile(ROOT_PATH . '/' . $module . '/Tests');
    }
    UnitTest::run();
}
