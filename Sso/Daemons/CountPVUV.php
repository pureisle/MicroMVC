<?php
/**
 * 统计每天pv、uv
 */
namespace Sso\Daemons;
use Framework\Libraries\Daemon;

class CountPVUV extends Daemon {
    public function init() {
        $num = count($this->getCurrentProcessList());
        if ($num > 1) {
            echo "last CountPVUV task not finish\n";
            exit();
        }
        echo "CountPVUV init\n";
    }
    public function doJob() {
        echo "CountPVUV run\n";
        sleep(30);
    }
}
