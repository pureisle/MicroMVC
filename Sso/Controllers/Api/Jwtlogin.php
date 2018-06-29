<?php
/**
 * Json Web Token 登陆接口
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\JsonWebToken;
use Sso\Models\Log;
use Sso\Models\User;

class Jwtlogin extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'name'   => 'requirement&not_empty',
        'passwd' => 'requirement&not_empty'
    );
    public function indexAction() {
        try {
            $params = $this->getGetParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        extract($params);
        //记录登陆日志
        Log::LoginUser($name, 'api_jwt');
        $user_obj = new User();
        $ret      = $user_obj->checkPasswd($passwd, $name);
        if (false == $ret) {
            ApiDisplay::display(ApiDisplay::PASSWD_CHECK_FAIL);
            return false;
        }
        $user_info = $user_obj->getInfoByName($name);
        $payload   = array(
            'uid'    => $user_info['uid'],
            'name'   => $user_info['name'],
            'extend' => $user_info['extend']
        );
        $access_token = JsonWebToken::sign($payload);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('access_token' => $access_token));
        return false;
    }
}