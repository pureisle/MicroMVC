<?php
/**
 * Debug类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\TestSuite;
use Framework\Libraries\Logger;

class TestLogger extends TestSuite {
    private $_logger = null;
    public function beginTest() {
        $this->_logger = new Logger('log.framework', 'Framework');
        $msg           = 'hello {name}';
        $param         = array(
            'name' => 'world'
        );
        $this->_logger->log(Logger::LEVEL_DEBUG, $msg, $param);
    }

}