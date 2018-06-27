<?php
/**
 * 修改用户信息
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\User;

class Update extends Controller {
    /**
     * 添加用户
     * !!!extend字段为覆盖更新!!!
     */
    public static $INDEX_PARAM_RULES = array(
        'uid'    => 'requirement&not_empty',
        'email'  => '',
        'tel'    => '',
        'status' => '',
        'extend' => ''
    );
    public function indexAction() {
        try {
            $params = $this->getGetParams();
            extract($params);
            if (empty($email) && empty($tel) && empty($extend) && ! isset($status)) {
                throw new \Exception('params empty');
            }
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $extend = json_decode($extend, true);
        if (empty($extend)) {
            $extend = array();
        }
        $user = new User();
        try {
            $ret = $user->updateInfo($uid, $email, $tel, $status, $extend);
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::FAIL_CODE, array($e->getMessage()));
            return false;
        }
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('data' => $ret));
        return false;
    }
    /**
     * 增量更新extend
     * 额外有一次用户查询，如无必要不要使用增量更新
     */
    public static $EXTEND_PARAM_RULES = array(
        'uid'    => 'requirement&not_empty',
        'extend' => 'requirement&not_empty',
        'email'  => '',
        'tel'    => '',
        'status' => ''

    );
    public function extendAction() {
        try {
            $params = $this->getGetParams();
            $extend = json_decode($params['extend'], true);
            if (empty($extend)) {
                throw new \Exception('extend jsondecode error');
            }
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $user       = new User();
        $user_info  = $user->getInfo($params['uid']);
        $new_extend = array_merge($user_info['extend'], $extend);
        try {
            $ret = $user->updateInfo($params['uid'], $params['email'], $params['tel'], $params['status'], $extend);
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::FAIL_CODE, array($e->getMessage()));
            return false;
        }
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('data' => $ret));
        return false;
    }
}