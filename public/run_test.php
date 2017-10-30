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
function main($config = array()) {
    $modules = $config['modules'];
    UnitTest::includeTestFile(FRAMEWORK_PATH . '/Tests');
    foreach ($modules as $module => $is_ok) {
        if ( ! $is_ok) {
            continue;
        }
        UnitTest::includeTestFile($module . '/Tests');
    }
    UnitTest::run();
}
