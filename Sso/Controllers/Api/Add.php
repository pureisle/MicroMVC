<?php
/**
 * 添加用户
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\User;

class Add extends Controller {
    /**
     * 添加用户
     */
    public static $INDEX_PARAM_RULES = array(
        'name'   => 'requirement&not_empty',
        'passwd' => 'requirement&not_empty',
        'email'  => '',
        'tel'    => '',
        'extend' => ''
    );
    public function indexAction() {
        try {
            $this->useAuth('api_auth');
            $params = $this->getPostParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        extract($params);
        $extend = json_decode($extend,true);
        if (empty($extend)) {
            $extend = array();
        }
        $user = new User();
        try {
            $ret = $user->addUser($name, $passwd, $email, $tel, $extend);
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::FAIL_CODE, array($e->getMessage()));
            return false;
        }
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('data' => $ret));
        return false;
    }
}
