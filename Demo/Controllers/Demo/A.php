<?php
namespace Demo\Controllers\Demo;
use Framework\Models\Controller;
use Framework\Models\LocalCurl;

class A extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'a' => 'requirement',
        'b' => 'number&max:15&min:10',
        'c' => 'timestamp',
        'd' => 'enum:a,1,3,5,b,12345'
    );
    public function indexAction() {
        $lc = new LocalCurl();
        $lc->setAction('test', 'http://123.12.12.12/xhprof/xhprof/run?a=1&b=2&c=3');
        // $lc->get('test');
        // var_dump($lc->body());
        $params = $this->getGetParams();
        var_dump('Demo_AController');
        // new test();
        $test = 'haha';

        $this->assign(array('test' => $test));
    }
}