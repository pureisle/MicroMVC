<?php
/**
 * 用户类
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Sso\Models;
use Framework\Libraries\SingletonManager;

class User {
    private static $NEW_USER_P_V        = 0;
    private static $NEW_USER_P_SALT_LEN = 32;
    private static $_user_info_cache    = array();
    const ONLINE_STATUS                 = 0;
    const OFFLINE_STATUS                = 1;
    private static $USER_STATUS         = array(
        self::ONLINE_STATUS  => array(
            'code' => self::ONLINE_STATUS,
            'name' => '在线'
        ),
        self::OFFLINE_STATUS => array(
            'code' => self::ONLINE_STATUS,
            'name' => '下线'
        )
    );
    public function __construct() {}
    /**
     * 查询用户信息
     * @param  int     $uid
     * @return array
     */
    public function getInfo(int $uid) {
        if ($uid <= 0) {
            return false;
        }
        if (isset(self::$_user_info_cache['uid']) && $uid == self::$_user_info_cache['uid']) {
            return self::$_user_info_cache;
        }
        $du_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $ret    = $du_obj->getInfoByUid($uid);
        if ( ! empty($ret)) {
            self::$_user_info_cache = $ret;
        }
        return $ret;
    }
    /**
     * 根据名字查询用户
     * @param    string $name
     * @return
     */
    public function getInfoByName(string $name) {
        if (empty($name)) {
            return false;
        }
        if (isset(self::$_user_info_cache['name']) && $name == self::$_user_info_cache['name']) {
            return self::$_user_info_cache;
        }
        $du_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $ret    = $du_obj->getInfoByName($name);
        if ( ! empty($ret)) {
            self::$_user_info_cache = $ret;
        }
        return $ret;
    }
    /**
     * 获取用户列表
     * @param  int|integer $count
     * @param  int|integer $page
     * @return array
     */
    public function getUserList(int $count = 10, int $page = 0) {
        $du_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $ret    = $du_obj->getListInfo($count, $page, 'DESC');
        return $ret;
    }
    /**
     * 创建新户
     * @param string      $name
     * @param string      $passwd
     * @param string|null $email
     * @param string|null $tel
     * @param array       $extend
     */
    public function addUser(string $name, string $passwd, string $email = null, string $tel = null, array $extend = array()) {
        if (empty($name) || empty($passwd)) {
            return false;
        }
        $du_obj        = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $p_v           = '';
        $salt          = '';
        $encode_passwd = $this->getEncodePasswd($passwd, $salt, $p_v);
        $ret           = $du_obj->addUser($name, $encode_passwd, $salt, $p_v, $email, $tel, $extend);
        return $ret;
    }
    /**
     * 修改用户信息
     * @param    int         $uid
     * @param    string|null $email
     * @param    string|null $tel
     * @param    array       $extend
     * @return
     */
    public function updateInfo(int $uid, string $email = null, string $tel = null, int $status = null, array $extend = array()) {
        $du_obj = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $data   = array();
        if ( ! empty($email)) {
            $data['email'] = $email;
        }
        if ( ! empty($tel)) {
            $data['tel'] = $tel;
        }
        if ( ! empty($extend)) {
            $data['extend'] = $extend;
        }
        if (in_array($status, array_keys(self::$USER_STATUS))) {
            $data['status'] = $status;
        }
        if (empty($data)) {
            return true;
        }
        $ret = $du_obj->updateByUid($uid, $data);
        return $ret;
    }
    public function _checkExtend($extend) {}
    /**
     * 修改密码
     * @param    int    $uid
     * @param    string $passwd
     * @return
     */
    public function updatePasswd(int $uid, string $passwd) {
        if ($uid <= 0 || empty($passwd)) {
            return false;
        }
        $du_obj         = SingletonManager::$SINGLETON_POOL->getInstance('\Sso\Data\User');
        $p_v            = '';
        $salt           = '';
        $encode_passwd  = $this->getEncodePasswd($passwd, $salt, $p_v);
        $data['passwd'] = $encode_passwd;
        $data['salt']   = $salt;
        $data['p_v']    = $p_v;
        $ret            = $du_obj->updateByUid($uid, $data);
        return $ret;
    }
    /**
     * 获取加密后的密码
     * @param  string   $passwd
     * @param  [type]   &$salt
     * @param  [type]   &$p_v
     * @return string
     */
    public function getEncodePasswd(string $passwd, &$salt, &$p_v) {
        if (empty($passwd)) {
            return false;
        }
        $salt           = $this->makeSalt(self::$NEW_USER_P_SALT_LEN);
        $p_v            = self::$NEW_USER_P_V;
        $encodeFunction = 'encodePasswdV' . $p_v;
        $encode_passwd  = $this->$encodeFunction($passwd, $salt);
        return $encode_passwd;
    }
    /**
     * 检查密码是否正确
     * @param    string $passwd
     * @param    string $name
     * @return
     */
    public function checkPasswd(string $passwd, string $name) {
        if (empty($passwd) || empty($name)) {
            return false;
        }
        $user_info = $this->getInfoByName($name);
        if (empty($user_info)) {
            return false;
        }
        $p_v            = $user_info['p_v'];
        $salt           = $user_info['salt'];
        $res_passwd     = $user_info['passwd'];
        $encodeFunction = 'encodePasswdV' . $p_v;
        $encode_passwd  = $this->$encodeFunction($passwd, $salt);
        return $res_passwd == $encode_passwd;
    }
    /**
     * 第0个版本密码编码
     * @param  string   $passwd
     * @return string
     */
    public function encodePasswdV0(string $passwd, string $salt) {
        if (empty($passwd) || empty($salt)) {
            return false;
        }
        $tmp = '';
        for ($i = 0; $i < strlen($passwd); $i++) {
            $s = isset($salt[$i]) ? $salt[$i] : '';
            $tmp .= $passwd[$i];
        }
        $ret = md5(md5($tmp) . $passwd);
        return $ret;
    }
    /**
     * 生产随机数
     * @param    integer $salt_len
     * @return
     */
    public function makeSalt(int $salt_len = 32) {
        // $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ,.\/<>?:;#@~()[]{}-_=+*^%$!';
        // $pattern=str_split($pattern);
        // shuffle($pattern);
        // $pattern=implode($pattern);
        $pattern = '97~YdPM,z4OtEWU(ruBa?G[_N/.w>c=O;0m^%HCh}FskAR$+VQyloveLjn:)iq]@ZS2#DXpgJ!\5T81I3-6fx{b<K*';
        $ret     = '';
        $i       = 0;
        for ($i; $i < $salt_len; $i++) {
            $index = mt_rand(0, strlen($pattern) - 1);
            $tmp   = $pattern[$index];
            $ret .= $tmp;
        }
        return $ret;
    }
}