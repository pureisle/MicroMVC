<?php
/**
 * 命令行运行引入入口
 *
 * 注意：引入cli.php文件前需要自定义 $env 环境变量，以便注册app
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
define("ROOT_PATH", realpath(dirname(__FILE__) . '/../'));
define('FRAMEWORK_NAME', 'Framework');
define('FRAMEWORK_PATH', ROOT_PATH . '/Framework');
define('CONFIG_FOLDER', 'config');
define('FRAMEWORK_CONFIG_PATH', FRAMEWORK_PATH . DIRECTORY_SEPARATOR . CONFIG_FOLDER);
define("FRAMEWORK_CONFIG_FILE", FRAMEWORK_CONFIG_PATH . DIRECTORY_SEPARATOR . FRAMEWORK_NAME . ".php");
require FRAMEWORK_PATH . '/Models/Application.php';
$app = new Framework\Models\Application($env['module']);
$app->bootstrap()->execute('main', $argv);