<?php
/**
 * 停用用户
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\User;

class Offline extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'uid' => 'requirement&not_empty'
    );
    public function indexAction() {
        try {
            $this->useAuth('api_auth');
            $params = $this->getPostParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $user = new User();
        try {
            $ret = $user->updateInfo($params['uid'], null, null, User::OFFLINE_STATUS);
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::FAIL_CODE, array($e->getMessage()));
            return false;
        }
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('data' => $ret));
        return false;
    }
}
