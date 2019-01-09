<?php
/**
 * 登陆页
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Controllers;
use Framework\Models\Controller;
use Sso\Models\Log;
use Sso\Models\User;

class Login extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'name'   => '',
        'passwd' => '',
        'url'    => ''
    );
    public function indexAction() {
        $this->usePolicy()->useXSS()->disableSniffing()->useFrame()->forceHTTPS();
        $params = $this->getGetParams();
        extract($params);
        if (empty($passwd) || empty($name)) {
            if (empty($url)) {
                $url = "http://t.cn";
            }
            $this->assign(array("url" => $url, 'test_xss' => array('a' => '<a href=xxx></a>', 'b' => 123)));
            return true;
        }
        //记录登陆日志
        Log::LoginUser($name, 'web');
        $user_obj = new User();
        $ret      = $user_obj->checkPasswd($passwd, $name);
        if (false == $ret) {
            $this->getView()->assign(array("text" => '登陆失败'));
            return true;
        }
        $user_info = $user_obj->getInfoByName($name);
        session_start();
        $_SESSION['uid']    = $user_info['uid'];
        $_SESSION['name']   = $user_info['name'];
        $_SESSION['extend'] = $user_info['extend'];
        //应该增加url安全域检验
        header("Location: " . $url);
        return false;
    }
}