<?php
/*
* 退出
*
*/
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\Log;

class Logout extends Controller {
	/**
	* 退出时，清除session
	*/
    public function indexAction() {
        //接口auth认证
        try {
            $this->useAuth('api_auth');
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::AUTH_FAILED, array($e->getMessage()));
            return false;
        }
    	session_start();
    	session_destroy();
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE);
        return false;
    }
}