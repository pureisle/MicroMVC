<?php
/**
 * 视图对象
 *
 * 主要提供视图渲染、视图变量注册等功能
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Models;

class View {
    private $_tpl_vars = array();
    public function __construct() {}
    /**
     * 渲染视图
     * @param  string   $view_path
     * @param  array    $tpl_vars
     * @return string
     */
    public function render(string $view_path, array $tpl_vars = array()) {
        if ( ! empty($tpl_vars)) {
            array_merge($this->_tpl_vars, $tpl_vars);
        }
        extract($this->_tpl_vars);
        ob_start();
        $ret  = include $view_path;
        $body = ob_get_contents();
        ob_end_clean();
        return $body;
    }
    /**
     * 设置渲染变量
     * @param  array  $var_arr
     * @return View
     */
    public function assign($var_arr) {
        $this->_tpl_vars = array_merge($this->_tpl_vars, $var_arr);
        return $this;
    }
}