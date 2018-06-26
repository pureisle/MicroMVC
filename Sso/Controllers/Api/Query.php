<?php
/**
 * 查询用户信息
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers\Api;
use Framework\Models\Controller;
use Sso\Models\ApiDisplay;
use Sso\Models\User;

class Query extends Controller {
    /**
     * 根据uid查信息
     */
    public static $INDEX_PARAM_RULES = array(
        'uid' => 'requirement&not_empty'
    );
    public function indexAction() {
        try {
            $params = $this->getGetParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $user = new User();
        $info = $user->getInfo($params['uid']);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('name' => $info['name'], 'uid' => $info['uid'], 'extend' => $info['extend']));
        return false;
    }
    /**
     * 根据用户名查信息
     */
    public static $NAME_PARAM_RULES = array(
        'name' => 'requirement&not_empty'
    );
    public function nameAction() {
        try {
            $params = $this->getGetParams();
        } catch (\Exception $e) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($e->getMessage()));
            return false;
        }
        $user = new User();
        $info = $user->getInfoByName($params['name']);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('name' => $info['name'], 'uid' => $info['uid'], 'extend' => $info['extend']));
        return false;
    }
}