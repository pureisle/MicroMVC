<?php
namespace Demo\Controllers;
use Framework\Models\Controller;
use Demo\Data\TestData;
use Framework\Libraries\ConfigTool;

class Index extends Controller {

   public function indexAction() {//默认Action
   	    // var_dump(ConfigTool:: loadByName('database_firehose','Demo'));
   		$test_data=new TestData();
       $this->getView()->assign(array("content"=> "Hello World"));
   }

}