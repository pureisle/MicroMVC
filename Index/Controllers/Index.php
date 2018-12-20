<?php
namespace Index\Controllers;
use Framework\Models\Controller;

class Index extends Controller {
    public function init() {
        // echo "init\n";
        //  safe_exit();//安全退出
    }
    public function indexAction() {
        echo "hello,MicroMVC~";
        return false;
    }

}