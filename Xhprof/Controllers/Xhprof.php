<?php
/**
 * php 性能调试工具类
 */
namespace Xhprof\Controllers;
use Framework\Models\Controller;
use Xhprof\Libraries\XHProfRuns;

class Xhprof extends Controller {
    public function runAction() {
        echo 'xhprof run';
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        register_shutdown_function(array($this, 'shutdown'));
        return false;
    }
    /*
     * 保存性能结果
     */
    public function shutdown() {
        $xhprof_data = xhprof_disable();
        $xhprof_runs = new XHProfRuns(LOG_ROOT_PATH.'/xhprof');
        $xhprof_name = 'framework_test';
        $run_id      = $xhprof_runs->save_run($xhprof_data, $xhprof_name);
        echo '<a href="/xhprof?run=' . $run_id . '&source=' . $xhprof_name . '" target="_blank">查看</a>';
    }
}
