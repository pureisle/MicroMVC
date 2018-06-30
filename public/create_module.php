<?php
/**
 * 创建一个新module
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
$env = array(
    'module' => 'Framework'
);
$bootstrap_str = <<<EOT
<?php
/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被调用,
 */
namespace {module_name};
use Framework\Models\Dispatcher;
define("{up_module_name}_MODULE_ROOT", ROOT_PATH . "/{module_name}");
class Bootstrap extends \Framework\Models\Bootstrap {
	public function _initDemo(Dispatcher \$dispatcher) {}
}
EOT;
require "cli.php";
function main($config, $argv) {
    if (empty($argv[1])) {
        die("Error: Module name emtpy.\n");
    }
    $module_name = ucfirst($argv[1]);
    $target_file = ROOT_PATH . "/" . $module_name;
    if (is_dir($target_file)) {
        die("Error: dir " . $target_file . " exist.\n");
    }
    mkdir($target_file, 0775);
    $create_dir_set = array('Libraries', 'Tests', 'Controllers', 'Views', 'Plugins', 'Cache', 'Data', 'Entities', 'Models', 'config');
    foreach ($create_dir_set as $dir_name) {
        $tmp = $target_file . "/" . $dir_name;
        $ret = mkdir($tmp, 0775);
    }
    global $bootstrap_str;
    $up_module_name = strtoupper($module_name);
    file_put_contents($target_file . "/Bootstrap.php", str_replace('{up_module_name}', $up_module_name, str_replace('{module_name}', $module_name, $bootstrap_str)));
    die("Create success!\n");
}
