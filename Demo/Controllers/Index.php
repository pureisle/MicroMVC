<?php
namespace Demo\Controllers;
use Framework\Models\Controller;

class Index extends Controller {
    public function indexAction() {
        $this->getView()->assign(array("content" => "Hello World"));
    }

}