<?php
/**
 * 并发Curl请求类
 *
 * example:
 * $mc = new MultiCurl();
 * $c1 = new Curl();
 * $c1->setAction('tmp', 'http://t.cn/fsgerge');
 * $c2 = new Curl();
 * $c2->setAction('tmp', 'http://t.cn/afwaefw');
 * $c3 = new Curl();
 * $c3->setAction('tmp', 'http://t.cn/awefwef');
 * $mc->addCurl(array($c1, $c2, $c3));
 * $mc->get('tmp')->exec();
 * var_dump($c1->body(),$c2->body(),$c3->body());
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class MultiCurl {
    private $_mch       = null;
    private $_curl_list = array();
    public function __construct() {
        $this->_mch = curl_multi_init();
    }
    /**
     * 添加Curl类
     * @param array $curl_list
     */
    public function addCurl($curl_list) {
        if ( ! is_array($curl_list)) {
            $curl_list = array($curl_list);
        }
        foreach ($curl_list as $curl) {
            $this->_curl_list[] = $curl;
            curl_multi_add_handle($this->_mch, $curl->getHandle());
            $curl->delayExec();
        }
        return $this;
    }
    /**
     * 获取已经注册的Curl
     * @return array
     */
    public function getCurlList() {
        return $this->_curl_list;
    }
    /**
     * 批量调用Curl类的设置方法,只适用于 Curl 类中包含的方法
     * @param    string $fun_name
     * @param    array  $params
     * @return
     */
    public function __call($fun_name, $params) {
        if (empty($this->_curl_list)) {
            return $this;
        }
        if ( ! method_exists(Curl::class, $fun_name)) {
            return false;
        }
        foreach ($this->_curl_list as $curl) {
            $curl->$fun_name(...$params);
        }
        return $this;
    }
    /**
     * 执行批量调用
     */
    public function exec() {
        if (empty($this->_curl_list)) {
            return array();
        }
        do {
            $tmp = '';
            $mrc = curl_multi_exec($this->_mch, $active);
            if ($mrc > 0) {
                $tmp = curl_multi_strerror($mrc);
            }
            $select_ret = curl_multi_select($this->_mch, 1); //通过性能测试看，发现有没有这一句都没啥明显区别，select()方法完全没有体现出省CPU的优势啊
        } while (CURLM_CALL_MULTI_PERFORM == $mrc || $active > 0);

        foreach ($this->_curl_list as $curl) {
            $result = curl_multi_getcontent($curl->getHandle());
            $curl->setResponseData($result);
            //测试了无法使用一个 curl_multi 句柄多次执行，所以就顺带的直接清理掉
            curl_multi_remove_handle($this->_mch, $curl->getHandle());
            // $curl->close(); //通过测试发现无需单独调用 curl 的 close() 方法。
        }
        curl_multi_close($this->_mch);
    }
}