<?php
namespace Index\Controllers;
use Framework\Models\Controller;

// use Framework\Models\LocalCurl;

class Index extends Controller {
    public function init() {
        // $lc = new LocalCurl();
        // $lc->setAction('test', 'http://127.0.0.1/xhprof/xhprof/run');
        // var_dump($lc->get('test')->body());
        // echo "init\n";
        // safe_exit();//安全退出
    }
    public function indexAction() {
        $lang = \Framework\Libraries\Tools::setLang('cn');
        $this->getView()->assign(array("text" => __('hello %s', 'MicroMVC~')));
    }
}