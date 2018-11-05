<?php
/**
 * 控制器基类
 *
 * 框架提供的Controller基类，主要提供的模板渲染的变量设置、请求参数获取及校验等功能
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\ConfigTool;
use Framework\Libraries\Exception;
use Framework\Libraries\Tools;
use Framework\Libraries\Validator;
use Framework\Models\Request;

abstract class Controller {
    const PARAM_SUFFIX = '_PARAM_RULES';
    private $_view;
    private $_request;
    public function __construct(Request $request) {
        $this->_request = $request;
    }
    /**
     * 设置模板渲染变量
     * @param    array $var_arr
     * @param    bool  $is_html_encode 是否进行html实体转义,可对输出内容做有限的xss防护
     * @return
     */
    public function assign(array $var_arr, bool $is_html_encode = true) {
        if ($is_html_encode) {
            $this->htmlEncode($var_arr);
        }
        return $this->_view->assign($var_arr);
    }
    private function htmlEncode(&$var_arr) {
        if (empty($var_arr)) {
            return;
        }
        foreach ($var_arr as $key => $value) {
            if (is_array($value)) {
                $this->htmlEncode($value);
                $var_arr[$key] = $value;
            } else {
                $var_arr[$key] = htmlspecialchars($value);
            }
        }
    }
    /**
     * 设置视图类
     * @param View $view
     */
    public function setView(View $view) {
        $this->_view = $view;
        return $this;
    }
    /**
     * 获取视图
     * @return View
     */
    public function getView() {
        return $this->_view;
    }
    /**
     * 获取Json参数，并进行参数校验
     * @return array
     */
    public function getJsonParams() {
        $data_json = file_get_contents('php://input');
        $params    = json_decode($data_json, true);
        if ( ! is_array($params)) {
            $params = array();
        }
        $ret_params = $this->_checkParams($params);
        return $ret_params;
    }
    /**
     * 获取post参数，并进行参数校验
     * @return array
     */
    public function getPostParams() {
        $params     = $this->_request->getPostParams();
        $ret_params = $this->_checkParams($params);
        return $ret_params;
    }
    /**
     * 获取get参数，并进行参数校验
     * @return array
     */
    public function getGetParams() {
        $params     = $this->_request->getGetParams();
        $ret_params = $this->_checkParams($params);
        return $ret_params;
    }
    /**
     * csrf检测，需要在输出前调用。
     *
     * 在需要验证的接口或页面的 前置controller的action接口内调用 csrfSet();
     * 然后在验证接口或页面调用 csrfCheck() 方法即可完成验证。
     */
    const CSRF_VAR_NAME     = 'CSRF_AUTH_TOKEN';
    const CSRF_TOKEN_EXPIRE = 3600;
    /**
     * 开启csrf验证
     */
    public function csrfSet(string $host = '') {
        if (empty($host)) {
            list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
        }
        $value = Tools::uniqid(6);
        $ret   = setcookie(self::CSRF_VAR_NAME, $value, time() + self::CSRF_TOKEN_EXPIRE, '/', $host, false, true);
        if ( ! $ret) {
            throw new ControllerException(ControllerException::ERROR_CSRF_AUTH_CHECK, 'cookie set error');
        }
        session_start();
        $_SESSION[self::CSRF_VAR_NAME] = $value;
    }
    /**
     * csrf 检测
     */
    public function csrfCheck() {
        if ( ! isset($_COOKIE[self::CSRF_VAR_NAME])) {
            throw new ControllerException(ControllerException::ERROR_CSRF_AUTH_CHECK, 'csrf auth check error');
        }
        session_start();
        if ($_SESSION[self::CSRF_VAR_NAME] != $_COOKIE[self::CSRF_VAR_NAME]) {
            throw new ControllerException(ControllerException::ERROR_CSRF_AUTH_CHECK, 'csrf auth check error');
        }
        unset($_SESSION[self::CSRF_VAR_NAME]); //验证后使其失效
    }
    /**
     * 接口使用auth认证
     *
     * 安全认证主要包括：
     *     1、app_secret参与的签名验证；需要开启参数use_sign = true 和设置 app_secret 值
     *     2、白名单验证，需要设置 white_ips , 值的格式为: 10.83,10.222.69.0/27,127.0.0.1,10.210.10,10
     *     3、请求时间有效性验证，在app_secret参与验证的基础上增加设置 valid_time值大于0，则会进行时间验证,该值的单位时间为10s
     *
     * 签名相关接收的参数为：
     *     app_key  接口调用方id
     *     app_sign  接口调用方加密后的签名
     *     app_time  如果需要时间有效性验证，则会覆盖占用该参数，接口参数定义不要使用这个参数
     *
     * app_secret 及 时间验证的签名规则：
     *     1、参数数组增加签证密钥，如：$params['app_secret']=$app_secret，如果需要验证时间，则需增加 $params['app_time']= intval(time() / 10);
     *     2、把参数数组构建为无下标的新数组,如： $tmp = array('param_a=1','param_b=stringxxx','app_secret=xxx')
     *     3、对新数组进行按字母生序排序,如： sort($tmp);
     *     4、使用字符"&"合并排序后的数组生成字符串,如： $params_str = implode('&',$tmp);
     *     5、使用md5获取哈希值，取前6位，至此获得参数的签名字符串,如： $sign = substr(md5($params_str), 0, 6);
     * @param     string $auth_config
     * @return
     */
    public function useAuth(string $auth_config) {
        $tmp                 = get_class($this);
        list($module, $null) = explode('\\', $tmp, 2);
        $config_set          = ConfigTool::loadByName($auth_config, $module);
        if (empty($config_set)) {
            throw new ControllerException(ControllerException::ERROR_API_AUTH_CHECK, 'auth config load fail');
        }
        $params = array();
        $get    = $this->_request->getGetParams();
        if ( ! empty($get)) {
            $params = $get;
        }
        $post = $this->_request->getPostParams();
        if ( ! empty($post)) {
            $params = array_merge($params, $post);
        }
        if ( ! isset($params['app_key']) || empty($params['app_key']) || empty($config_set[$params['app_key']])) {
            throw new ControllerException(ControllerException::ERROR_API_AUTH_CHECK, 'app_key error');
        }
        $config = $config_set[$params['app_key']];
        $error  = '';
        $ret    = ControllerAuth::check($config, $params, $error);
        if ( ! $ret) {
            throw new ControllerException(ControllerException::ERROR_API_AUTH_CHECK, $error);
        }
        return true;
    }
    /**
     * 参数校验
     * @param  array  $params
     * @return bool
     */
    private function _checkParams(array $params) {
        $action_name    = $this->_request->getAction();
        $var_name       = strtoupper($action_name) . self::PARAM_SUFFIX;
        $class_name     = get_class($this);
        $check_key_list = array();
        if ( ! isset($class_name::$$var_name)) {
            return array();
        }
        $rule_set = $class_name::$$var_name;
        $v        = new Validator();
        $ret      = $v->check($params, $rule_set);
        if ( ! $ret) {
            throw new ControllerException(ControllerException::ERROR_PARAM_CHECK, $v->getErrorMsg());
        }
        $result = array_intersect_key($params, $rule_set);
        return $result;
    }
}

class ControllerException extends Exception {
    const ERROR_PARAM_CHECK     = 1;
    const ERROR_API_AUTH_CHECK  = 2;
    const ERROR_CSRF_AUTH_CHECK = 3;
    public $ERROR_SET           = array(
        self::ERROR_PARAM_CHECK     => array(
            'code'    => self::ERROR_PARAM_CHECK,
            'message' => 'param check error.'
        ),
        self::ERROR_API_AUTH_CHECK  => array(
            'code'    => self::ERROR_API_AUTH_CHECK,
            'message' => 'api auth check error.'
        ),
        self::ERROR_CSRF_AUTH_CHECK => array(
            'code'    => self::ERROR_CSRF_AUTH_CHECK,
            'message' => 'csrf error.'
        )
    );
}