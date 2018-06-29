<?php
/**
 * Json Web Token 验证接口
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\JsonWebToken;

class Jwtcheck extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'access_token' => 'requirement&not_empty'
    );
    public function indexAction() {
        try {
            $params = $this->getGetParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $access_token = $params['access_token'];
        $user_info    = JsonWebToken::verify($access_token);
        if (false == $user_info) {
            ApiDisplay::display(ApiDisplay::FAIL_CODE, array('check fail'));
            return false;
        }
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array($user_info));
        return false;
    }
}