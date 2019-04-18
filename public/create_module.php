<?php
/**
 * 创建一个新module
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
if (substr(php_sapi_name(), 0, 3) !== 'cli') {
    die("This Programe can only be run in CLI mode");
}
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
use Framework\Models\Bootstrap as FB;
class Bootstrap extends FB {
    public function _initDemo(Dispatcher \$dispatcher) {}
}
EOT;
$controller_str = <<<EOT
<?php
/**
 * 控制器
 */
namespace {module_name}\Controllers;
use Framework\Models\Controller;

class Index extends Controller {
    public static \$INDEX_PARAM_RULES = array(
        'a'   => '',
        'b'   => '',
    );
    public function indexAction() {
        // \$this->useCORS();
        //\$this->usePolicy()->useXSS()->disableSniffing()->useFrame()->forceHTTPS();
        \$params = \$this->getGetParams();
         \$this->getView()->assign(array("text" => 'hello,MicroMVC.'));
        //\$this->localtion(\$url);
    }
}
EOT;
$view_str = <<<EOT
<html>
<head>
<title>micro-mvc</title>
</head>
<body>
    <p><?php echo \$text; ?></p>
  </body>
</html>
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
    $path = $config['path'];
    foreach ($path as $key => $dir_name) {
        if ("/" === $dir_name[0]) {
            $tmp = $dir_name;
        } else {
            $tmp = $target_file . "/" . $dir_name;
        }
        if (is_dir($tmp)) {
            continue;
        }
        $ret = mkdir($tmp, 0775);
        echo "cteated dir :" . $tmp . "\n";
    }
    global $bootstrap_str;
    file_put_contents($target_file . "/Bootstrap.php", str_replace('{module_name}', $module_name, $bootstrap_str));
    global $controller_str;
    file_put_contents($target_file . '/' . $path['controller'] . "/Index.php", str_replace('{module_name}', $module_name, $controller_str));
    global $view_str;
    $tmp = $target_file . '/' . $path['view'] . "/Index";
    mkdir($tmp, 0775);
    file_put_contents($tmp . "/index.phtml", $view_str);
    die("Create success!\n");
}
