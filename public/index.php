<?php
/**
 * 入口文件，以下代码均具有顺序要求，变更时需要注意
 */
define("ROOT_PATH", realpath(dirname(__FILE__) . '/../'));
define('FRAMEWORK_NAME', 'Framework');
define('FRAMEWORK_PATH', ROOT_PATH . '/Framework');
define('CONFIG_FOLDER', 'config');
define('FRAMEWORK_CONFIG_PATH', FRAMEWORK_PATH . DIRECTORY_SEPARATOR . CONFIG_FOLDER);
define("FRAMEWORK_CONFIG_FILE", FRAMEWORK_CONFIG_PATH . DIRECTORY_SEPARATOR . FRAMEWORK_NAME . ".php");
//框架路由解析路径顺序
if (isset($_SERVER['PATH_INFO'])) {
    $server_uri = $_SERVER['PATH_INFO'];
} else if (isset($_SERVER['REQUEST_URI'])) {
    $server_uri = $_SERVER['REQUEST_URI'];
} else if (isset($_SERVER['ORIG_PATH_INFO'])) {
    $server_uri = $_SERVER['ORIG_PATH_INFO'];
} else {
    $server_uri = '';
}
//默认module名
$module = 'Demo';
//解析module名
if ( ! empty($server_uri)) {
    $tmp    = explode('/', $server_uri);
    $module = ucfirst($tmp[1]);
}
require FRAMEWORK_PATH . '/Models/Application.php';
$app = new Framework\Models\Application($module);
$app->bootstrap()->run();