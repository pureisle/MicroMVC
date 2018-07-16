<?php
/**
 * 请求对象类实体
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Entities;

class Request {
    protected $_request_uri    = '';
    protected $_uri            = null;
    protected $_get_params     = null;
    protected $_post_params    = null;
    protected $_request_params = array();
    public function __construct() {
        $this->_request_uri            = $this->getEnvUri();
        $this->_request_params['GET']  = $_GET;
        $this->_request_params['POST'] = $_POST;
    }
    /**
     * 获取实体uri，优先使用设置的uri，若无设置则使用request_uri
     * @return string
     */
    public function getUri() {
        if (isset($this->_uri)) {
            return $this->_uri;
        } else {
            return $this->_request_uri;
        }
    }
    /**
     * 设置实体uri
     * @param string $uri
     */
    public function setUri($uri) {
        return $this->_uri = $uri;
    }
    public function setGetParams($params) {
        $this->_get_params = $params;
        return $this;
    }
    public function getGetParams() {
        if (isset($this->_get_params)) {
            return $this->_get_params;
        }
        return $this->_request_params['GET'];
    }
    public function setPostParams($params) {
        $this->_post_params = $params;
        return $this;
    }
    public function getPostParams() {
        if (isset($this->_post_params)) {
            return $this->_post_params;
        }
        return $this->_request_params['POST'];
    }
    public function getEnvUri() {
        if (isset($_SERVER['PATH_INFO'])) {
            $server_uri = $_SERVER['PATH_INFO'];
        } else if (isset($_SERVER['REQUEST_URI'])) {
            $tmp = explode('?', $_SERVER['REQUEST_URI'],2);
            $server_uri=$tmp[0];
        } else if (isset($_SERVER['ORIG_PATH_INFO'])) {
            $server_uri = $_SERVER['ORIG_PATH_INFO'];
        } else {
            $server_uri = '';
        }
        return $server_uri;
    }
}