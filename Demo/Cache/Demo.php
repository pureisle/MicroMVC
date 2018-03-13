<?php
/**
 * 缓存管理样例
 */
namespace Demo\Cache;
use Framework\Libraries\ControllCache;

class Demo extends ControllCache {
    const CONFIG_NAME          = 'mc.business_a';
    const INFO_CACHE_KEY_INDEX = 'info';
    const LIST_CACHE_KEY_INDEX = 'list';
    public $key_sets           = array(
        self::INFO_CACHE_KEY_INDEX => array('rule' => 'Demo\Cache->info_id:{id}', 'expire' => 2),
        self::LIST_CACHE_KEY_INDEX => array('rule' => 'Demo\Cache->list_page:{page}_count:{count}', 'expire' => 10)
    );
    public function __construct() {
        parent::__construct(self::CONFIG_NAME);
    }
    /**
     * 根据id获取信息
     * 可以通过callback来设置缓存址
     *
     * @param  int   $id
     * @return mix
     */
    public function getInfo($id) {
        $ret = parent::get(self::INFO_CACHE_KEY_INDEX, array('id' => $id),
            function ($memc, $key, &$value) {
                $value = 'callback value';
                return true;
            }
        );
        return $ret;
    }
    /**
     * 设置信息
     * @param int $id
     * @param mix $value
     */
    public function setInfo($id, $value) {
        $ret = parent::set(self::INFO_CACHE_KEY_INDEX, array('id' => $id), $value);
        return $ret;
    }
    public function appendInfo($id, $value) {
        $ret = parent::append(self::INFO_CACHE_KEY_INDEX, array('id' => $id), $value);
        return $ret;
    }
    public function getList($page, $count) {
        $ret = parent::get(self::LIST_CACHE_KEY_INDEX, array('page' => $page, 'count' => $count));
        return $ret;
    }
    public function setList($page, $count, $value) {
        $ret = parent::set(self::LIST_CACHE_KEY_INDEX, array('page' => $page, 'count' => $count), $value);
        return $ret;
    }
}
