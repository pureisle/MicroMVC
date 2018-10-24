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
        $this->useAuth('api_auth');
        $params = $this->getPostParams();
        $user   = new User();
        $info   = $user->getInfo($params['uid']);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('name' => $info['name'], 'uid' => $info['uid'], 'extend' => $info['extend'], 'tel' => $info['tel'], 'email' => $info['email'], 'status' => $info['status']));
        return false;
    }
    /**
     * 根据用户名查信息
     */
    public static $NAME_PARAM_RULES = array(
        'name' => 'requirement&not_empty'
    );
    public function nameAction() {
        $this->useAuth('api_auth');
        $params = $this->getPostParams();
        $user   = new User();
        $info   = $user->getInfoByName($params['name']);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, array('name' => $info['name'], 'uid' => $info['uid'], 'extend' => $info['extend'], 'tel' => $info['tel'], 'email' => $info['email'], 'status' => $info['status']));
        return false;
    }

    /**
     * 获取用户列表
     */
    public static $LIST_PARAM_RULES = array(
        'count' => '',
        'page'  => ''
    );
    public function listAction() {
        $this->useAuth('api_auth');
        $params    = $this->getPostParams();
        $user      = new User();
        $data_info = $user->getUserList($params['count'], $params['page']);
        if ( ! empty($data_info)) {
            foreach ($data_info as &$item) {
                unset($item['passwd'], $item['p_v'], $item['salt']);
            }
        }
        $data['info']  = $data_info;
        $data['count'] = $user->countUser();
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, $data);
        return false;
    }

    /**
     * 批量获取用户信息
     */
    public static $MUTILGETUSER_PARAM_RULES = array(
        'uids' => ' '
    );
    public function mutilGetUserAction() {
        $this->useAuth('api_auth');
        $params = $this->getPostParams();
        $user   = new User();
        $data   = $user->multiGetUser($params['uids']);
        ApiDisplay::display(ApiDisplay::SUCCESS_CODE, $data);
        return false;
    }
}