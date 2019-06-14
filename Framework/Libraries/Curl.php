<?php
/**
 * Curl 管理底层类
 *
 * 提供get、post、put、delete等基本的http请求，支持代理、ssl方式的请求
 *
 * example:
 * $manager = new Curl ();
 * $manager->setAction ( 'lg', 'http://wappass.weibo.com/', 'http://t.cn' )->cookie ()->post ( 'lg', $data );
 * var_dump ( $manager->header () );
 * var_dump ( $manager->body () );
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class Curl {
    private $_header;
    private $_body;
    private $_ch;
    // private $_proxy_type = 'HTTP';
    // private $_proxy_auth = 'BASIC';
    private $_proxy;
    private $_proxy_port;
    private $_proxy_user;
    private $_proxy_pass;
    protected $_cookie;
    protected $_options;
    protected $_url      = array();
    protected $_referer  = array();
    private $_outHeader  = '';
    private $_curl_shell = array();
    private $_is_delay   = false;
    /**
     * 构造方法，初始化HttpRequest对象
     *
     * @param  array    $options
     * @return object
     */
    public function __construct($default = array(), $header = array('Expect:')) {
        $this->_options               = array();
        $this->_options['time_out']   = 30;
        $this->_options['temp_root']  = sys_get_temp_dir();
        $this->_options['user_agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; zh-CN; rv:1.8.1.20) Gecko/20081217 Firefox/2.0.0.20';
        $this->_options['proxy_type'] = 'HTTP';  // or SOCKS5
        $this->_options['proxy_auth'] = 'BASIC'; // or NTLM
        if (is_array($default)) {
            foreach ($default as $key => $value) {
                $this->_options[$key] = $value;
            }
        }
        $this->_curl_shell['begin'] = 'curl -v';
        $this->init($header);
    }
    /**
     * 初始化curl请求信息
     *
     * @param  array    $header=array('Expect:')
     * @return object
     */
    public function init($header = array('Expect:')) {
        if ( ! empty($this->_ch)) {
            curl_close($this->_ch);
        }
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_HEADER, true);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_options['user_agent']);
        $this->_curl_shell['-A'] = '-A "' . $this->_options['user_agent'] . '"';
        $this->timeOutForConnect($this->_options['time_out']);
        $this->setHeader($header);
        $this->_header = '';
        $this->_body   = '';
        return $this;
    }
    /**
     * 延迟发起请求
     * @return
     */
    public function delayExec() {
        $this->_is_delay = true;
        return $this;
    }
    /**
     * 取消延迟，直接发起请求
     * @return $this
     */
    public function exec() {
        $this->_is_delay = false;
        $this->_request();
        return $this;
    }
    public function getError() {
        return curl_error($this->_ch);
    }
    /**
     * 获取命令行请求字符串,必须要在发起请求前调用 delayExec() 方法,该方法才可正常使用
     * 帮助调试
     * @return string
     */
    public function getShellCurl() {
        $end = $this->_curl_shell['end'];
        unset($this->_curl_shell['end']);
        $tmp = implode(' ', $this->_curl_shell);
        $tmp .= ' ' . $end;
        return $tmp;
    }
    /**
     * 设置请求超时时间
     *
     * @param  int      $time
     * @return object
     */
    public function timeOutForConnect($time) {
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $time);
        $this->_curl_shell['--connect-timeout'] = '--connect-timeout ' . $time;
        return $this;
    }
    /**
     * 设置curl执行超时时间
     *
     * @param  int      $time
     * @return object
     */
    public function timeOutForExecute($time) {
        $this->setOpt(CURLOPT_TIMEOUT, $time);
        $this->_curl_shell['--max-time'] = '--max-time ' . $time;
        return $this;
    }
    /**
     * 设置毫秒超时时间
     */
    public function timeOut($ms) {
        $this->setOpt(CURLOPT_NOSIGNAL, 1);     //注意，毫秒超时一定要设置这个.cURL 7.16.2中被加入。从PHP 5.2.3起可使用
        $this->setOpt(CURLOPT_TIMEOUT_MS, $ms); //单位为毫秒
        return $this;
    }
    /**
     * 设置curl参数
     * @param string $opt_name
     * @param mix    $value
     */
    public function setOpt($opt_name, $value) {
        $this->_options['exec_' . $opt_name] = $value;
        curl_setopt($this->_ch, $opt_name, $value);
        return $this;
    }
    /**
     * 设置请求信息
     *
     * @param  string   $action
     * @param  string   $url
     * @param  string   $referer=''
     * @return object
     */
    public function setAction($action, $url, $referer = '') {
        $this->_url[$action]     = $url;
        $this->_referer[$action] = $referer;
        return $this;
    }
    /**
     * 设置header信息
     *
     * @param  array    $header
     * @return object
     */
    public function setHeader($header = array('Expect:')) {
        if (is_array($header)) {
            foreach ($header as $one) {
                list($header_name, $value) = explode(':', $one, 2);
                if ( ! empty($value)) {
                    $this->_curl_shell['header_' . $header_name] = '--Header "' . $one . '"';
                }
            }
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        }
        $this->_outHeader = $header;
        return $this;
    }
    /**
     * 关闭连接
     *
     * @return void
     */
    public function close() {
        if (is_resource($this->_ch)) {
            curl_close($this->_ch);
        }
        if (isset($this->_cookie) && is_file($this->_cookie)) {
            unlink($this->_cookie);
        }
    }
    /**
     * 使用cookie
     *
     * @return object
     */
    public function cookie() {
        if ( ! isset($this->_cookie)) {
            if ( ! empty($this->_cookie) && is_file($this->_cookie)) {
                unlink($this->_cookie);
            }
            $this->_cookie = tempnam($this->_options['temp_root'], 'curl_manager_cookie_');
        }
        curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->_cookie);
        curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $this->_cookie);
        return $this;
    }
    /**
     * 自定义cookie内容
     *
     * @param  string   $cookie
     * @return object
     */
    public function setCookie($cookie) {
        $this->cookie();
        if (empty($cookie)) {
            return $this;
        }
        $file_handle = fopen($this->_cookie, 'w');
        if (fwrite($file_handle, $cookie) === false) {
            echo "warning: cookie写入失败";
        }
        $this->_curl_shell['header_Cookie'] = '--Header "Cookie: ' . $cookie . '"';
        return $this;
    }
    /**
     * https请求
     *
     * @return object
     */
    public function ssl() {
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
        return $this;
    }
    /**
     * 使用代理服务器
     *
     * @return object
     */
    public function proxy($host = null, $port = null, $type = null, $user = null, $pass = null, $auth = null) {
        isset($type) && $this->_options['proxy_type'] = $type;
        isset($auth) && $this->_options['proxy_auth'] = $auth;
        isset($host) && $this->_proxy                 = $host;
        isset($port) && $this->_proxy_port            = $port;
        isset($user) && $this->_proxy_user            = $user;
        isset($pass) && $this->_proxy_pass            = $pass;
        if ( ! empty($this->_proxy)) {
            curl_setopt($this->_ch, CURLOPT_PROXYTYPE, 'HTTP' == $this->_options['proxy_type'] ? CURLPROXY_HTTP : CURLPROXY_SOCKS5);
            curl_setopt($this->_ch, CURLOPT_PROXY, $this->_proxy);
            curl_setopt($this->_ch, CURLOPT_PROXYPORT, $this->_proxy_port);
        }
        if ( ! empty($this->_proxy_user)) {
            curl_setopt($this->_ch, CURLOPT_PROXYAUTH, 'BASIC' == $this->_proxy_auth ? CURLAUTH_BASIC : CURLAUTH_NTLM);
            curl_setopt($this->_ch, CURLOPT_PROXYUSERPWD, "[{$this->_proxy_user}]:[{$this->_proxy_pass}]");
        }
        return $this;
    }
    /**
     * 停止使用代理服务器
     *
     * @return object
     */
    public function stopProxy() {
        curl_setopt($this->_ch, CURLOPT_PROXY, false);
        return $this;
    }
    /**
     * 发起post请求
     *
     * @param  $action        行为名称
     * @param  $query=array() 支持文件上传，所对应的参数键值前需要@符号,值为文件的绝对路径
     * @return object
     */
    public function post($action, $query = array()) {
        if (empty($this->_url[$action])) {
            return false;
        }
        $query = $this->_buildQuery($query);
        curl_setopt($this->_ch, CURLOPT_POST, true);
        curl_setopt($this->_ch, CURLOPT_URL, $this->_url[$action]);
        curl_setopt($this->_ch, CURLOPT_REFERER, $this->_referer[$action]);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $query);
        $this->_curl_shell['--referer'] = '--referer "' . $this->_referer[$action] . '"';
        $this->_curl_shell['--data']    = '--data "' . $query . '"';
        $this->_curl_shell['end']       = '"' . $this->_url[$action] . '"';
        $this->_request();
        return $this;
    }
    /**
     * 发起get请求
     *
     *            $action
     *            $query=array()
     * @param
     * @param
     * @return  object
     */
    public function get($action, $query = array()) {
        if (empty($this->_url[$action])) {
            return false;
        }
        $url = $this->_url[$action];
        if ( ! empty($query)) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= is_array($query) ? http_build_query($query) : $query;
        }
        curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt($this->_ch, CURLOPT_REFERER, $this->_referer[$action]);
        $this->_curl_shell['--referer'] = '--referer "' . $this->_referer[$action] . '"';
        $this->_curl_shell['end']       = '"' . $url . '"';
        $this->_request();
        return $this;
    }
    /**
     * 对重定向结果进行跳转后的页面请求
     *
     *            $max=-1
     * @param
     * @return  object
     */
    public function followLocation($max = -1) {
        preg_match('#Location:\s*(.+)#i', $this->header(), $match);
        if (0 == $max) {
            return $this;
        }
        if (isset($match[1])) {
            $url = parse_url($match[1]);
            if (empty($url['host'])) {
                $tmp = parse_url($this->effectiveUrl());
                $url = $tmp['host'] . $match[1];
            } else {
                $url = $match[1];
            }
            $url = trim($url);
            $this->setAction('auto_location_gateway', $url, $this->effectiveUrl());
            $this->get('auto_location_gateway')->followLocation($max - 1);
        }
        $this->_curl_shell['-L'] = '-L';
        return $this;
    }
    // ps：写上curl原生的follow location是想着可能有效率上的优势，自己写的有灵活上的优势
    public function followLocationSetOpt($action, $max = -1) {
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->_ch, CURLOPT_AUTOREFERER, true);
        $this->_curl_shell['-L'] = '-L';
        if ($max > 0) {
            curl_setopt($this->_ch, CURLOPT_MAXREDIRS, $max);
        }
        $this->get($action);
        curl_setopt($this->_ch, CURLOPT_MAXREDIRS, false);
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->_ch, CURLOPT_AUTOREFERER, false);
        return $this;
    }
    public function put($action, $query = array()) {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        return $this->post($action, $query);
    }
    public function delete($action, $query = array()) {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->post($action, $query);
    }
    public function head($action, $query = array()) {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        return $this->post($action, $query);
    }
    public function options($action, $query = array()) {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        return $this->post($action, $query);
    }
    public function trace($action, $query = array()) {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'TRACE');
        return $this->post($action, $query);
    }
    public function connect() {}
    /**
     * 获取上一次成功请求的header数据
     */
    public function header() {
        return $this->_header;
    }
    /**
     * 获取上一次成功请求的body数据
     */
    public function body() {
        return $this->_body;
    }
    /**
     * 获取上一次成功请求的url
     */
    public function effectiveUrl() {
        return $this->_getInfo(CURLINFO_EFFECTIVE_URL);
    }
    /**
     * 获取上一次成功请求的http code
     */
    public function httpCode() {
        return $this->_getInfo(CURLINFO_HTTP_CODE);
    }
    /**
     * 获取一个action的url
     */
    public function getUrl($action_name) {
        return $this->_url[$action_name];
    }
    /**
     * 获取curl版本信息
     */
    public function curlVersion() {
        return curl_version();
    }
    public function getLastRequestInfo($info_name = '') {
        $info_key = array(
            'CURLINFO_EFFECTIVE_URL',
            'CURLINFO_HTTP_CODE ',
            'CURLINFO_FILETIME',
            'CURLINFO_TOTAL_TIME ',
            'CURLINFO_NAMELOOKUP_TIME',
            'CURLINFO_CONNECT_TIME',
            'CURLINFO_PRETRANSFER_TIME',
            'CURLINFO_STARTTRANSFER_TIME ',
            'CURLINFO_REDIRECT_TIME',
            'CURLINFO_SIZE_UPLOAD',
            'CURLINFO_SIZE_DOWNLOAD',
            'CURLINFO_SPEED_DOWNLOAD ',
            'CURLINFO_SPEED_UPLOAD ',
            'CURLINFO_HEADER_SIZE',
            'CURLINFO_HEADER_OUT',
            'CURLINFO_REQUEST_SIZE ',
            'CURLINFO_SSL_VERIFYRESULT ',
            'CURLINFO_CONTENT_LENGTH_DOWNLOAD',
            'CURLINFO_CONTENT_LENGTH_UPLOAD',
            'CURLINFO_CONTENT_TYPE'
        );
        return $this->_getInfo($info_name);
    }
    /**
     * 析构函数，尽量自行手动执行close方法
     */
    public function __destruct() {
        $this->close();
    }
    /**
     * 获取上一次请求的部分信息
     */
    private function _getInfo($info_name) {
        if (empty($info_name)) {
            $result = curl_getinfo($this->_ch);
        } else {
            $result = curl_getinfo($this->_ch, $info_name);
        }
        return $result;
    }
    /**
     * 请求数据
     */
    private function _request() {
        if ($this->_is_delay) {
            return false;
        }
        $response = curl_exec($this->_ch);
        Debug::debugDump($this->_curl_shell);
        $this->_curl_shell = array('begin' => 'curl -v');
        $errno             = curl_errno($this->_ch);
        if ($errno > 0) {
            throw new CurlRequestException($errno, curl_error($this->_ch));
        }
        $this->setResponseData($response);
    }
    /**
     * 构建请求参数字符串
     *
     * @param  array    $params
     * @return string
     */
    private function _buildQuery($params) {
        return http_build_query($params);

        $o = '';
        foreach ($params as $k => $v) {
            if ('@' != $k{0}) {
                $o .= urlencode($k);
            } else {
                $o .= '@' . urlencode(substr($k, 1));
            }
            $o .= '=' . urlencode($v) . '&';
        }
        $params = substr($o, 0, -1);
        return $params;
    }
    public function setResponseData($response) {
        if (empty($response)) {
            return;
        }
        $header_size   = $this->_getInfo(CURLINFO_HEADER_SIZE);
        $this->_header = substr($response, 0, $header_size);
        $this->_body   = substr($response, $header_size);
    }
    public function getHandle() {
        return $this->_ch;
    }
}
/**
 * http请求异常类
 */
class CurlRequestException extends Exception {
    const ERROR_CUEL_CODE = 0;
    public $ERROR_SET     = array();
    public function __construct($code = 0, $msg = '') {
        $this->ERROR_SET[$code] = array(
            'code'    => $code,
            'message' => $msg
        );
        parent::__construct($code);
    }
}