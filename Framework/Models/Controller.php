<?php
/**
 * 控制器基类
 *
 * 框架提供的Controller基类，主要提供的模板渲染的变量设置、请求参数获取及校验等功能
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;
use Framework\Libraries\Exception;
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
     * @return
     */
    public function assign($var_arr) {
        return $this->_view->assign($var_arr);
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
            return false;
        }
        $result = array_intersect_key($params, $rule_set);
        return $result;
    }
}

class ControllerException extends Exception {
    const ERROR_PARAM_CHECK = 1;
    public $ERROR_SET       = array(
        self::ERROR_PARAM_CHECK => array(
            'code'    => self::ERROR_PARAM_CHECK,
            'message' => 'param check error.'
        )
    );
}