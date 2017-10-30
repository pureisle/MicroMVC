<?php
/**
 * xhprof启动检测
 */
namespace Xhprof;

class Bootstrap extends \Framework\Models\Bootstrap {
    public function _initCheckExtension() {
        if ( ! extension_loaded('xhprof')) {
            echo "Please check whether the xhprof extension is available.";
            exit();
        }
    }
}
