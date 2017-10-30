<?php
/**
 * redis操作公共底层
 * 使用方法：
 * 1、添加一个模块时，在redis.ini中配置该模块的redis池的名字
 * 2、new Redis（池子名称）
 * Note：调用类里的方法时，请去掉后缀“Redis”，如调用 $redis->delRedis()，需要写成
 *       $redis->del();
 */
namespace framework;
class Redis {
    private $_redis_conf    = null;
    private static  $redis_object;
    private static $arr_obj;
    private $_theredisprefix;//key前缀，为模块名

    public static $globalmasterflag = false;

    const POSITION_BEFORE      = 'BEFORE';
    const POSITION_AFTER       = 'AFTER';
    const WITHSCORES           = 'WITHSCORES';
    const REDIS_CONF_FILE_NAME = 'redis.ini';
    const REDIS_WRITE_METHODS  = array('del', 'expire', 'expireat', 'move', 'persist', 'rename', 'renamenx', 'sort', 'append', 'set', 'decr', 'decrby', 'getset', 'incr', 'incrby', 'mset', 'msetnx', 'setbit', 'setex', 'setnx', 'setrange', 'hdel', 'hincrby', 'hmset', 'hset', 'hsetnx', 'blpop', 'brpop', 'brpoplpush', 'linsert', 'lpop', 'lpush', 'lpushx', 'lrem', 'lremove', 'lset', 'rpop', 'rpoplpush', 'rpush', 'rpushx', 'sadd', 'smove', 'spop', 'srem', 'sunionstore', 'zadd', 'zincrby', 'zinterstore', 'zrem', 'zremrangebyrank', 'zremrangebyscore', 'zunionstore');


    public function __construct($redis_pool_name) {
        if (empty($redis_pool_name)) {
            throw new RedisException(RedisException::ERROR_POOL_NAME_EMPTY);
        }
        $this->_theredisprefix = $redis_pool_name.":";
        $this->_redis_conf    = ParseIni::getConfig(self::REDIS_CONF_FILE_NAME, $redis_pool_name);
    }

    /*
     * 设置是否强读主库
     *
     * @param $flag true-强读   false-不强读
     *  forceMaster(true) 后， redis对象的后续请求都会走主库
     *  如不需要 需再次调用forceMaster(false)恢复正常
     *
     */
    public function forceMaster($flag = true) {
        self::$globalmasterflag = $flag;
    }

    public function __call($method, $args) {
        try {
            //配置文件
            $redis_conf_arr      = $this->_redis_conf;
            $redis_write_methods = self::REDIS_WRITE_METHODS;

            if (empty($args[0]) || ! is_string($args[0])) {
                return false;
            }

            $thekey = $args[0];
            //添加前缀
            if ( ! empty($this->_theredisprefix)) {
                $thekey  = $this->_theredisprefix . $thekey;
                $args[0] = $thekey;
            }

            //默认上行操作走从库
            $masterorslave = 'slave';
            //下行操作走主库
            if (in_array($method, $redis_write_methods)) {
                $masterorslave = 'master';
            }
            if (true === self::$globalmasterflag) {
                $masterorslave = 'master';
            }
            $redis_conf = array();
            $redis_conf = $redis_conf_arr[$masterorslave];
            if (empty($redis_conf)) {
                // key didnot match any regex
                return false;
            }

            /////////////////// redis_conf ok, connect
            RunTime::start("Redis-" . $redis_conf['host'] . ":" . $redis_conf['port'] . "-" . $method . "-" . var_export($args, true));
            $conn = $this->_connect($redis_conf['host'], $redis_conf['port']);
            RunTime::stop("Redis-" . $redis_conf['host'] . ":" . $redis_conf['port'] . "-" . $method . "-" . var_export($args, true));

            if (isset($_GET['debugredis']) && $_GET['debugredis']) {
                var_dump($redis_conf);
            }
            if ($conn) {
                return call_user_func_array(array($this, $method . "Redis"), $args);
            } else {
                return false;
            }
        } catch (RedisException $e) {
            return false;
        }
    }

    /**
     * 私有方法：连接数据库
     * @param  [string] $host 服务器地址
     * @param  [number] $port 端口号
     * @return [bool] 是否成功
     */
    private function _connect($host, $port) {
        $bool = true;
        $key  = $host . ":" . $port;
        if ( ! isset(self::$arr_obj[$key])) {
            //printf("create $key\n");
            self::$arr_obj[$key] = new \Redis();
        }
        self::$redis_object = self::$arr_obj[$key];

        if ( ! self::$redis_object->isConnected()) {
            //printf("connect $key\n");
            RunTime::start('redisconnect ' . $key);
            $bool = self::$redis_object->connect($host, $port);
            RunTime::stop('redisconnect ' . $key);
            //写日志，以后加上
            // if ( ! $bool) {
            //     $blm = SingletonManager::$SINGLETON_POOL->getInstance('Boke_LogModel');
            //     $blm->add(
            //         array(
            //             'type' => 55,
            //             'code' => '',
            //             'data' => json_encode(array($host, $port)),
            //             'ext'  => ''
            //         )
            //     );
            // }
        }
        return $bool;
    }

    /**
     *
     * @abstract 删除一个key
     * @param  string $keys
     * @return int
     */
    public function delRedis($key) {
        if ( ! is_array($key)) {
            $key = func_get_args();
        }
        return self::$redis_object->del($key);
    }

    /**
     * @abstract 判断key是否已存在
     * @param  string $key
     * @return int
     */
    public function existsRedis($key) {
        return self::$redis_object->exists($key);
    }

    /**
     * @abstract 设置key的有效时间，单位为秒
     * Set a key's time to live in seconds
     * @param  string    $key
     * @param  int       $seconds
     * @return boolean
     */
    public function expireRedis($key, $seconds) {
        return self::$redis_object->expire($key, $seconds);
    }

    /**
     * @abstract 设置key的有效时间，unix时间戳
     * Set the expiration for a key as a UNIX timestamp
     * @param  string $key
     * @param  int    $timestamp
     * @return int
     */
    public function expireatRedis($key, $timestamp) {
        return self::$redis_object->expireat($key, $timestamp);
    }

    /**
     * Move key from the currently selected database (see SELECT) to the specified destination database.
     * When key already exists in the destination database, or it does not exist in the source database, it does
     * nothing.
     * It is possible to use MOVE as a locking primitive because of this.
     * @param  int   $db
     * @return int
     */
    public function moveRedis($key, $db) {
        return self::$redis_object->move($key, $db);
    }

    /**
     * Remove the expiration from a key
     * @param  string $key
     * @return int
     */
    public function persistRedis($key) {
        return self::$redis_object->persist($key);
    }

    /**
     * 重命名一个key
     * Renames key to newkey.
     * It returns an error when the source and destination names are the same, or when key does not exist.
     * If newkey already exists it is overwritten.
     * @param  string    $key
     * @param  string    $newkey
     * @return boolean
     */
    public function renameRedis($key, $newkey) {
        return self::$redis_object->rename($key, $newkey);
    }

    /**
     * 如果新的key还没有使用，可以用它重命名一个key
     * Rename a key, only if the new key does not exist
     * @param  string $key
     * @param  string $newkey
     * @return int
     */
    public function renamenxRedis($key, $newkey) {
        return self::$redis_object->renamenx($key, $newkey);
    }

    /**
     * Sort the elements in a list, set or sorted set
     * @key string
     * @sort_rule:  Options: array(key => value, ...) - optional, with the following keys and values:
     * 'by' => 'some_pattern_*',
     * 'limit' => array(0, 1),
     * 'get' => 'some_other_pattern_*' or an array of patterns,
     * 'sort' => 'asc' or 'desc',
     * 'alpha' => TRUE,
     * 'store' => 'external-key'
     * Return value
     * An array of values, or a number corresponding to the number of elements stored if that was used.
     */
    public function sortRedis($key, $sort_rule = '') {
        if ($sort_rule) {
            return self::$redis_object->sort($key, $sort_rule);
        } else {
            return self::$redis_object->sort($key);
        }
    }

    /**
     * @abstract 获取一个key的剩余有效时间
     * Get the time to live for a key
     * @param  string $key
     * @return int
     */
    public function ttlRedis($key) {
        return self::$redis_object->ttl($key);
    }

    /**
     * @获取一个key里存储的数据类型
     * Returns the string representation of the type of the value stored at key.
     * Depending on the type of the data pointed by the key, this method will return the following value:
     * string: Redis::REDIS_STRING 1
     * set: Redis::REDIS_SET 2
     * list: Redis::REDIS_LIST 3
     * zset: Redis::REDIS_ZSET 4
     * hash: Redis::REDIS_HASH 5
     * other: Redis::REDIS_NOT_FOUND 0
     * @return string
     */
    public function typeRedis($key) {
        return self::$redis_object->type($key);
    }

    /**
     * Describes the object pointed to by a key
     * he information to retrieve (string) and the key (string). Info can be one of the following:
     * "encoding"
     * "refcount"
     * "idletime"
     * Return value
     * STRING for "encoding", LONG for "refcount" and "idletime", FALSE if the key doesn't exist.
     * @return maxed
     */
    public function objectRedis($key, $subcommand) {
        return self::$redis_object->object($subcommand, $key);
    }

    /**
     * @category string
     * @abstract 在一个key的值后面追加一个值
     * Append a value to a key
     * @param  string $key
     * @param  string $value
     * @return int
     */
    public function appendRedis($key, $value) {
        return self::$redis_object->append($key, $value);
    }

    /**
     * @category string
     * @abstract 获取key的值
     * Get the value of key
     * @param  string   $key
     * @return string
     */
    public function getRedis($key) {
        return self::$redis_object->get($key);
    }

    /**
     * @category string
     * @abstract 设置key的值
     * set key to hold the string value
     * @param  string $key
     * @param  string $value
     * @return always OK since SET can't fail
     */
    public function setRedis($key, $value) {
        return self::$redis_object->set($key, $value);
    }

    /**
     * @category string
     * @abstract key的（整数）值减1
     * Decrement the integer value of a key by one
     * @param  string $key
     * @return int
     */
    public function decrRedis($key) {
        return self::$redis_object->decr($key);
    }

    /**
     * @category string
     * @abstract key的（整数）值减 n
     * Decrement the integer value of a key by the given number
     * @param  string $key
     * @param  int    $decrement
     * @return int
     */
    public function decrbyRedis($key, $decrement) {
        return self::$redis_object->decrby($key, $decrement);
    }

    /**
     * Returns the bit value at offset in the string value stored at key
     * @param string $key
     * @param int    $offset
     */
    public function getbitRedis($key, $offset) {
        return self::$redis_object->getbit($key, $offset);
    }

    /**
     * Get a substring of the string stored at a key
     * @param  string   $key
     * @param  int      $start
     * @param  int      $end
     * @return string
     */
    public function getrangeRedis($key, $start, $end) {
        return self::$redis_object->getrange($key, $start, $end);
    }

    /**
     * Atomically sets key to value and returns the old value stored at key.
     * Returns an error when key exists but does not hold a string value.
     *
     * From time to time we need to get the value of the counter and reset it to zero atomically.
     * This can be done using GETSET mycounter "0".
     * @param  string   $key
     * @param  string   $value
     * @return string
     */
    public function getsetRedis($key, $value) {
        return self::$redis_object->getset($key, $value);
    }

    /**
     * Increment the integer value of a key by one
     * @param  string $key
     * @return int
     */
    public function incrRedis($key) {
        return self::$redis_object->incr($key);
    }

    /**
     * Increment the integer value of a key by the given number
     * @param  string $key
     * @param  int    $increment
     * @return int
     */
    public function incrbyRedis($key, $increment) {
        return self::$redis_object->incrby($key, $increment);
    }

    /**
     * Returns the values of all specified keys.
     * For every key that does not hold a string value or does not exist, the special value nil is returned.
     * Parameters: $key, [key ...]
     * or: array($key1, $key2...)
     * @param  string  $key
     * @return array
     */
    public function mgetRedis($key) {
        $args = func_get_args();
        if (count($args) > 1) {
            return call_user_func_array(
                array(self::$redis_object, 'mget'),
                array($args)
            );
        }
        return self::$redis_object->mget(array($key));
    }

    public function msetRedis($key, $value) {
        $args = func_get_args();
        if (count($args) > 2) {
            $newargs = array();
            for ($i = 0; $i < count($args); $i++) {
                if ($i % 2 == 0) {
                    $newargs[$args[$i]] = $args[$i + 1];
                }
            }

            return call_user_func_array(
                array(self::$redis_object, 'mset'),
                array($newargs)
            );
        }
        return self::$redis_object->mset(array($key => $value));
    }

    /**
     * sets or clears the bit at offset in the string value stored at key
     * Returns the original bit value stored at offset.
     * @link http://redis.io/commands/setbit
     *
     * @param  string $key
     * @param  int    $offset
     * @param  int    $value
     * @return int
     */
    public function setbitRedis($key, $offset, $value) {
        return self::$redis_object->setbit($key, $offset, $value);
    }

    /**
     * Set the value and expiration of a key
     * @param  string    $key
     * @param  int       $seconds
     * @param  string    $value
     * @return boolean
     */
    public function setexRedis($key, $seconds, $value) {
        return self::$redis_object->setex($key, $seconds, $value);
    }

    /**
     * Set the value of a key, only if the key does not exist
     * @param string $key
     * @param string $value
     */
    public function setnxRedis($key, $value) {
        return self::$redis_object->setnx($key, $value);
    }

    /**
     * Overwrites part of the string stored at key, starting at the specified offset, for the entire length of
     * value.
     * If the offset is larger than the current length of the string at key, the string is padded with zero-bytes
     * to make offset fit.
     * Non-existing keys are considered as empty strings, so this command will make sure it holds a string large
     * enough
     * to be able to set value at offset.
     *
     * Thanks to SETRANGE and the analogous GETRANGE commands, you can use Redis strings as a linear array with O
     * (1) random access.
     * This is a very fast and efficient storage in many real world use cases.
     * Returns the length of the string after it was modified by the command.
     * @link http://redis.io/commands/setrange
     *
     * @param  string $key
     * @param  int    $offset
     * @param  string $value
     * @return int
     */
    public function setrangeRedis($key, $offset, $value) {
        return self::$redis_object->setrange($key, $offset, $value);
    }

    /**
     * Get the length of the value stored in a key
     * @param  string $key
     * @return int
     */
    public function strlenRedis($key) {
        return self::$redis_object->strlen($key);
    }

    /**
     * removes the specified fields from the hash stored at key.
     * Non-existing fields are ignored. Non-existing keys are treated as empty hashes and this command returns 0.
     * Parameters: ($key, $field1, $field2...)
     * or: ($key, array($field1,$field2...))
     * @param  $key
     * @param  array|string $field
     * @return int
     */
    public function hdelRedis($key, $field) {
        if (is_array($field)) {
            array_unshift($field, $key);
        } else {
            $field = array($key, $field);
        }
        $args = func_get_args();
        if (count($args) > 2) {
            $field = $args;
        }
        return call_user_func_array(
            array(self::$redis_object, 'hdel'),
            $field
        );
    }

    /**
     * Determine if a hash field exists
     * @param  string $key
     * @param  string $field
     * @return int
     */
    public function hexistsRedis($key, $field) {
        return self::$redis_object->hexists($key, $field);
    }

    /**
     * Get the value of a hash field
     * @param  string       $key
     * @param  string       $field
     * @return string|int
     */
    public function hgetRedis($key, $field) {
        return self::$redis_object->hget($key, $field);
    }

    /**
     * Get all the fields and values in a hash
     * @param  string  $key
     * @return array
     */
    public function hgetallRedis($key) {
        return $arr = self::$redis_object->hgetall($key);
    }

    /**
     * Increments the number stored at field in the hash stored at key by increment.
     * If key does not exist, a new key holding a hash is created.
     * If field does not exist or holds a string that cannot be interpreted as integer, the value is set to 0
     * before the operation is performed.
     * Returns the value at field after the increment operation.
     * @param  string $key
     * @param  string $field
     * @param  int    $increment
     * @return int
     */
    public function hincrbyRedis($key, $field, $increment) {
        return self::$redis_object->hincrby($key, $field, $increment);
    }

    /**
     * Get all the fields in a hash
     * @param  string  $key name of hash
     * @return array
     */
    public function hkeysRedis($key) {
        return self::$redis_object->hkeys($key);
    }

    /**
     * Get the number of fields in a hash
     * @param  string $key
     * @return int
     */
    public function hlenRedis($key) {
        return self::$redis_object->hlen($key);
    }

    /**
     * Returns the values associated with the specified fields in the hash stored at key.
     * For every field that does not exist in the hash, a nil value is returned.
     * @param  string  $key
     * @param  array   $fields
     * @return array
     */
    public function hmgetRedis($key, array $fields) {
        return self::$redis_object->hmget($key, $fields);
    }

    /**
     * Set multiple hash fields to multiple values
     * @param string $key
     * @param array  $fields (field => value)
     */
    public function hmsetRedis($key, array $fields) {
        return self::$redis_object->hmset($key, $fields);
    }

    /**
     * Set the string value of a hash field
     * @param  string $key     hash
     * @param  string $field
     * @param  string $value
     * @return int
     */
    public function hsetRedis($key, $field, $value) {
        return self::$redis_object->hset($key, $field, $value);
    }

    /**
     * Set the value of a hash field, only if the field does not exist
     * @param  string $key
     * @param  string $field
     * @param  string $value
     * @return int
     */
    public function hsetnxRedis($key, $field, $value) {
        return self::$redis_object->hsetnx($key, $field, $value);
    }

    /**
     * Get all the values in a hash
     * @param  string  $key
     * @return array
     */
    public function hvalsRedis($key) {
        return self::$redis_object->hvals($key);
    }

    /**
     * Remove and get the first element in a list, or block until one is available
     * Parameters format:
     * array(key1,key2,keyN), timeout
     * @param string $key
     * @param int    $timeout - time of waiting
     */
    public function blpopRedis($key, $timeout) {
        return self::$redis_object->blpop($key, $timeout);
    }

    /**
     * Remove and get the last element in a list, or block until one is available
     * Parameters format:
     * array(key1,key2,keyN), timeout
     * @param string $key
     * @param int    $timeout - time of waiting
     */
    public function brpopRedis($key, $timeout) {
        return self::$redis_object->brpop($key, $timeout);
    }

    /**
     * Pop a value from a list, push it to another list and return it; or block until one is available
     * @param  string           $source
     * @param  string           $destination
     * @param  int              $timeout
     * @return string|boolean
     */
    public function brpoplpushRedis($source, $destination, $timeout) {
        return self::$redis_object->brpoplpush($source, $destination, $timeout);
    }

    /**
     * Returns the element at index $index in the list stored at $key.
     * The index is zero-based, so 0 means the first element, 1 the second element and so on.
     * Negative indices can be used to designate elements starting at the tail of the list.
     * Here, -1 means the last element, -2 means the penultimate and so forth.
     * When the value at key is not a list, an error is returned.
     * @param  string           $key
     * @param  int              $index
     * @return string|boolean
     */
    public function lindexRedis($key, $index) {
        return self::$redis_object->lindex($key, $index);
    }

    /**
     * Insert an element before or after another element in a list
     * @param  string $key
     * @param  bool   $after
     * @param  string $pivot
     * @param  string $value
     * @return int
     */
    public function linsertRedis($key, $after = true, $pivot, $value) {
        if ($after) {
            $position = self::POSITION_AFTER;
        } else {
            $position = self::POSITION_BEFORE;
        }

        return self::$redis_object->linsert($key, $position, $pivot, $value);
    }

    /**
     * Get the length of a list
     * @param  string $key
     * @return int
     */
    public function llenRedis($key) {
        return self::$redis_object->llen($key);
    }

    /**
     * Remove and get the first element in a list
     * @param  string           $key
     * @return string|boolean
     */
    public function lpopRedis($key) {
        return self::$redis_object->lpop($key);
    }

    /**
     * Inserts value at the head of the list stored at key.
     * If key does not exist, it is created as empty list before performing the push operation.
     * When key holds a value that is not a list, an error is returned.
     * @param  string $key
     * @param  string $value
     * @return int
     */
    public function lpushRedis($key, $value) {
        return self::$redis_object->lpush($key, $value);
    }

    /**
     * Inserts value at the head of the list stored at key, only if key already exists and holds a list.
     * In contrary to LPush, no operation will be performed when key does not yet exist.
     * @param  string $key
     * @param  string $value
     * @return int
     */
    public function lpushxRedis($key, $value) {
        return self::$redis_object->lpushx($key, $value);
    }

    /**
     * Returns the specified elements of the list stored at key.
     * The offsets $start and $stop are zero-based indexes, with 0 being the first element of the list (the head
     * of the list),
     * 1 being the next element and so on.
     * These offsets can also be negative numbers indicating offsets starting at the end of the list.
     * For example, -1 is the last element of the list, -2 the penultimate, and so on.
     * @param  string  $key
     * @param  int     $start
     * @param  int     $stop
     * @return array
     */
    public function lrangeRedis($key, $start, $stop) {
        return self::$redis_object->lrange($key, $start, $stop);
    }

    /**
     * removes all value
     */
    public function lremRedis($key, $value) {
        return self::$redis_object->lrem($key, $value);
    }

    /**
     * Removes the first count occurrences of elements equal to value from the list stored at key.
     * The count argument influences the operation in the following ways:
     * count > 0: Remove elements equal to value moving from head to tail.
     * count < 0: Remove elements equal to value moving from tail to head.
     * count = 0: Remove all elements equal to value.
     * For example, LREM list -2 "hello" will remove the last two occurrences of "hello" in the list stored at
     * list.
     * @param  string $key
     * @param  int    $count
     * @param  string $value
     * @return int
     */
    public function lremoveRedis($key, $value, $count) {
        return self::$redis_object->lremove($key, $value, $count);
    }

    /**
     * Sets the list element at index to value.
     * For more information on the index argument, see LINDEX.
     * An error is returned for out of range indexes.
     * @param  $key
     * @param  $index
     * @param  $value
     * @return boolean
     */
    public function lsetRedis($key, $index, $value) {
        return self::$redis_object->lset($key, $index, $value);
    }

    /**
     * get list element
     * @param  $key
     * @param  $value
     * @return string
     */
    public function lgetRedis($key, $index) {
        return self::$redis_object->lget($key, $index);
    }

    /**
     * Trim a list to the specified range
     * @link http://redis.io/commands/ltrim
     *
     * @param  string    $key
     * @param  int       $start
     * @param  int       $stop
     * @return boolean
     */
    public function ltrimRedis($key, $start, $stop) {
        return self::$redis_object->ltrim($key, $start, $stop);
    }

    /**
     * Removes and returns the last element of the list stored at key.
     * @param  string           $key
     * @return string|boolean
     */
    public function rpopRedis($key) {
        return self::$redis_object->rpop($key);
    }

    /**
     * Atomically returns and removes the last element (tail) of the list stored at source,
     * and pushes the element at the first element (head) of the list stored at destination.
     * If source does not exist, the value nil is returned and no operation is performed.
     * @param  string   $source
     * @param  string   $destination
     * @return string
     */
    public function rpoplpushRedis($source, $destination) {
        return self::$redis_object->rpoplpush($source, $destination);
    }

    /**
     * Inserts value at the tail of the list stored at key.
     * If key does not exist, it is created as empty list before performing the push operation.
     * When key holds a value that is not a list, an error is returned.
     * key, array(value,value,...)
     * @param  string        $key
     * @param  string|array  $value
     * @return int|boolean
     */
    public function rpushRedis($key, $value) {
        return self::$redis_object->rpush($key, $value);
    }

    /**
     * Append a value to a list, only if the list exists
     * @param  string $key
     * @param  string $value
     * @return int
     */
    public function rpushxRedis($key, $value) {
        return self::$redis_object->rpushx($key, $value);
    }

    /**
     * Add a member to a set
     * @param  string    $key
     * @param  string    $value
     * @return boolean
     */
    public function saddRedis($key, $value) {
        if (is_array($value)) {
            $args = array($key);
            $args = array_merge($args, $value);
            return call_user_func_array(
                array(self::$redis_object, 'sadd'),
                $args
            );
        }
        return self::$redis_object->sadd($key, $value);
    }

    /**
     * Get the number of members in a set
     * @param  string $key
     * @return int
     */
    public function scardRedis($key) {
        return self::$redis_object->scard($key);
    }

    /**
     * Returns the members of the set resulting from the difference between the first set and all the successive
     * sets.
     * For example:
     * key1 = {a,b,c,d}
     * key2 = {c}
     * key3 = {a,c,e}
     * SDIFF key1 key2 key3 = {b,d}
     * Keys that do not exist are considered to be empty sets.
     *
     * Parameters: key1, key2, key3...
     * @param  string|array $key
     * @return array
     */
    public function sdiffRedis($key = array()) {
        if ( ! is_array($key)) {
            $key = func_get_args();
        }
        if (self::$redis_object && count($key) > 0) {
            return self::$redis_object->sdiff($key);
        } else {
            return false;
        }
    }

    /**
     * Returns the members of the set resulting from the intersection of all the given sets.
     * For example:
     * key1 = {a,b,c,d}
     * key2 = {c}
     * key3 = {a,c,e}
     * SINTER key1 key2 key3 = {c}
     * Parameters: key [key ...]
     * or: array(key, key, ...)
     * @param  string|array $key
     * @return array
     */
    public function sinterRedis($key = '') {
        if ( ! is_array($key)) {
            $key = func_get_args();
        }
        if (self::$redis_object && count($key) > 1) {
            return self::$redis_object->sinter($key);
        } else {
            return false;
        }
    }

    /**
     *  确定一个给定的值是否是一个集合的成员
     * Returns if value is a member of the set.
     * @param  string    $key
     * @param  string    $value
     * @return boolean
     */
    public function sismemberRedis($key, $value) {
        return self::$redis_object->sismember($key, $value);
    }

    /**
     * 获取集合里面的所有成员.
     * @param  string  $key
     * @return array
     */
    public function smembersRedis($key) {
        return self::$redis_object->smembers($key);
    }

    /**
     * Move member from the set at source to the set at destination.
     * This operation is atomic.
     * In every given moment the element will appear to be a member of source or destination for other clients.
     * If the source set does not exist or does not contain the specified element, no operation is performed and 0
     * is returned.
     * Otherwise, the element is removed from the source set and added to the destination set.
     * When the specified element already exists in the destination set, it is only removed from the source set.
     * @param  string $source
     * @param  string $destination
     * @param  string $member
     * @return int
     */
    public function smoveRedis($source, $destination, $member) {
        return self::$redis_object->smove($source, $destination, $member);
    }

    /**
     * Remove and return a random member from a set
     * @param  string $key
     * @return string the removed element
     */
    public function spopRedis($key) {
        return self::$redis_object->spop($key);
    }

    /**
     * Get a random member from a set
     * @param  string   $key
     * @return string
     */
    public function srandmemberRedis($key) {
        return self::$redis_object->srandmember($key);
    }

    /**
     * Remove member from the set. If 'value' is not a member of this set, no operation is performed.
     * An error is returned when the value stored at key is not a set.
     * @param  string    $key
     * @param  string    $value
     * @return boolean
     */
    public function sremRedis($key, $value) {
        return self::$redis_object->srem($key, $value);
    }

    /**
     * Returns the members of the set resulting from the union of all the given sets.
     * For example:
     * key1 = {a,b,c,d}
     * key2 = {c}
     * key3 = {a,c,e}
     * SUNION key1 key2 key3 = {a,b,c,d,e}
     * Parameters: key [key...]
     * @param  string|array $key
     * @return array
     */
    public function sunionRedis($key) {
        if ( ! is_array($key)) {
            $key = func_get_args();
        }
        return self::$redis_object->sunion($key);
    }

    /**
     * Add a member to a sorted set, or update its score if it already exists
     * @param  string $key
     * @param  int    $score
     * @param  string $member
     * @return int
     */
    public function zaddRedis($key, $score, $member) {
        $args = func_get_args();
        if (count($args) > 3) {
            return call_user_func_array(
                array(self::$redis_object, 'zadd'),
                $args
            );
        }
        return self::$redis_object->zadd($key, $score, $member);
    }

    /**
     * Get the number of members in a sorted set
     * @param  string $key
     * @return int
     */
    public function zcardRedis($key) {
        return self::$redis_object->zcard($key);
    }

    /**
     * Returns the number of elements in the sorted set at key with a score between min and max.
     * The min and max arguments have the same semantic as described for ZRANGEBYSCORE.
     * @param  string     $key
     * @param  string|int $min
     * @param  string|int $max
     * @return int
     */
    public function zcountRedis($key, $min, $max) {
        return self::$redis_object->zcount($key, $min, $max);
    }

    /**
     * Increment the score of a member in a sorted set
     * @param  string   $key
     * @param  number   $increment
     * @param  string   $member
     * @return number
     */
    public function zincrbyRedis($key, $increment, $member) {
        return self::$redis_object->zincrby($key, $increment, $member);
    }

    /**
     * @param  string  $key
     * @param  int     $start
     * @param  int     $stop
     * @param  bool    $withscores
     * @return array
     */
    public function zrangeRedis($key, $start, $stop, $withscores = false) {
        if ($withscores) {
            return self::$redis_object->zrange($key, $start, $stop, self::WITHSCORES);
        } else {
            return self::$redis_object->zrange($key, $start, $stop);
        }
    }

    /**
     * Return a range of members in a sorted set, by score
     * @link http://redis.io/commands/zrangebyscore
     *
     * @param  string        $key
     * @param  string|number $min
     * @param  string|number $max
     * @param  array         $args| $args=array('withscore'=>,'limit'=>array($offset,$count))
     * @return array
     */
    public function zrangebyscoreRedis($key, $min, $max, array $args = null) {
        if ($args) {
            return self::$redis_object->zrangebyscore($key, $min, $max, $args);
        } else {
            return self::$redis_object->zrangebyscore($key, $min, $max);
        }
    }

    /**
     * Returns the rank of member in the sorted set stored at key, with the scores ordered from low to high.
     * The rank (or index) is 0-based, which means that the member with the lowest score has rank 0.
     * Use ZREVRANK to get the rank of an element with the scores ordered from high to low.
     * @param  string        $key
     * @param  string        $member
     * @return int|boolean
     */
    public function zrankRedis($key, $member) {
        return self::$redis_object->zrank($key, $member);
    }

    /**
     * Remove a member from a sorted set
     * @param  string $key
     * @param  string $member
     * @return int
     */
    public function zremRedis($key, $member) {
        $args = func_get_args();
        if (count($args) > 2) {
            return call_user_func_array(
                array(self::$redis_object, 'zrem'),
                $args
            );
        }
        return self::$redis_object->zrem($key, $member);
    }

    /**
     * Removes all elements in the sorted set stored at key with rank between start and stop.
     * Both start and stop are 0-based indexes with 0 being the element with the lowest score.
     * These indexes can be negative numbers, where they indicate offsets starting at the element with the highest
     * score.
     * For example: -1 is the element with the highest score, -2 the element with the second highest score and so
     * forth.
     * Returns the number of elements removed.
     * @param  string $key
     * @param  int    $start
     * @param  int    $stop
     * @return int
     */
    public function zremrangebyrankRedis($key, $start, $stop) {
        return self::$redis_object->zremrangebyrank($key, $start, $stop);
    }

    /**
     * Remove all members in a sorted set within the given scores
     * @param  string        $key
     * @param  string|number $min
     * @param  string|number $max
     * @return int
     */
    public function zremrangebyscoreRedis($key, $min, $max) {
        return self::$redis_object->zremrangebyscore($key, $min, $max);
    }

    /**
     * Returns the specified range of elements in the sorted set stored at key.
     * The elements are considered to be ordered from the highest to the lowest score.
     * Descending lexicographical order is used for elements with equal score.
     * @param  string  $key
     * @param  int     $start
     * @param  int     $stop
     * @param  bool    $withscores
     * @return array
     */
    public function zrevrangeRedis($key, $start, $stop, $withscores = false) {
        if ($withscores) {
            return self::$redis_object->zrevrange($key, $start, $stop, self::WITHSCORES);
        } else {
            return self::$redis_object->zrevrange($key, $start, $stop);
        }
    }

    /**
     * Returns all the elements in the sorted set at key with a score between max and min
     * (including elements with score equal to max or min).
     * In contrary to the default ordering of sorted sets, for this command
     * the elements are considered to be ordered from high to low scores.
     * The elements having the same score are returned in reverse lexicographical order.
     * @param  string  $key
     * @param  number  $max
     * @param  number  $min
     * @param  array   $args| $args=array('withscore'=>,'limit'=>array($offset,$count))
     * @return array
     */
    public function zrevrangebyscoreRedis($key, $max, $min, array $args = null) {
        if ($args) {
            return self::$redis_object->zrevrangebyscore($key, $max, $min, $args);
        } else {
            return self::$redis_object->zrevrangebyscore($key, $max, $min);
        }
    }

    /**
     * Returns the rank of member in the sorted set stored at key, with the scores ordered from high to low.
     * The rank (or index) is 0-based, which means that the member with the highest score has rank 0.
     * Use ZRANK to get the rank of an element with the scores ordered from low to high.
     * @param  string        $key
     * @param  string        $member
     * @return int|boolean
     */
    public function zrevrankRedis($key, $member) {
        return self::$redis_object->zrevrank($key, $member);
    }

    /**
     * Get the score associated with the given member in a sorted set
     * @param  string   $key
     * @param  string   $member
     * @return string
     */
    public function zscoreRedis($key, $member) {
        return self::$redis_object->zscore($key, $member);
    }

    /**
     * Flushes all previously queued commands in a transaction and restores the connection state to normal.
     * If WATCH was used, DISCARD unwatches all keys.
     */
    public function discardRedis() {
        return self::$redis_object->discard();
    }

    /**
     * Executes all previously queued commands in a transaction and restores the connection state to normal.
     * When using WATCH, EXEC will execute commands only if the watched keys were not modified, allowing for a
     * check-and-set mechanism.
     */
    public function execRedis() {
        return self::$redis_object->exec();
    }

    /**
     * Mark the start of a transaction block
     */
    public function multiRedis() {
        return self::$redis_object->multi();
    }

    /**
     * Forget about all watched keys
     */
    public function unwatchRedis() {
        return self::$redis_object->unwatch();
    }

    /**
     * Marks the given keys to be watched for conditional execution of a transaction
     * each argument is a key:
     * watch('key1', 'key2', 'key3', ...)
     */
    public function watchRedis() {
        return self::$redis_object->watch($args);
    }

    /**
     * Close the connection
     */
    public function quitRedis() {
        self::$redis_object->close();
    }

    /**Ping the server*/
    public function pingRedis() {
        return self::$redis_object->ping();
    }

    public function bgrewriteaofRedis() {
        return self::$redis_object->bgrewriteaof();
    }

    /**
     * Asynchronously save the dataset to disk
     */
    public function bgsaveRedis() {
        return self::$redis_object->bgsave();
    }

    /**
     * Resets the statistics reported by Redis using the INFO command.
     * These are the counters that are reset:
     * Keyspace hits
     * Keyspace misses
     * Number of commands processed
     * Number of connections received
     * Number of expired keys
     */
    public function configResetstatRedis() {
        return self::$redis_object->config_resetstat();
    }

    /**
     * Return the number of keys in the selected database
     * @return int
     */
    public function dbsizeRedis() {
        return self::$redis_object->dbsize();
    }

    /**
     * Get information and statistics about the server
     */
    public function infoRedis() {
        return self::$redis_object->info();
    }

    /**
     * Remove all keys from the current database
     */
    public function flushdbRedis() {
        return self::$redis_object->flushdb();
    }

    /**
     * Remove all keys from all databases
     */
    public function flushallRedis() {
        return self::$redis_object->flushall();
    }

    /**Get debugging information about a key */
    public function debugSegfaultRedis() {
        return self::$redis_object->debug_segfault();
    }

    /**Get the UNIX time stamp of the last successful save to disk Ping the server
     * @return int
     */
    public function lastsaveRedis() {
        return self::$redis_object->lastsave();
    }

    /**Listen for all requests received by the server in real time
     * @return maxed
     */
    public function monitorRedis() {
        return self::$redis_object->monitor();
    }

    /**
     * Synchronously save the dataset to disk
     * @return maxed
     */
    public function saveRedis() {
        return self::$redis_object->save();
    }

    /**Synchronously save the dataset to disk and then shut down the server
     * @return maxed
     */
    public function shutdownRedis() {
        return self::$redis_object->shutdown();
    }

    /**
     * Internal command used for replication
     * @return maxed
     */
    public function syncRedis() {
        return self::$redis_object->sync();
    }

    public function getLastError() {
        return self::$redis_object->getLastError();
    }
}

class RedisException extends Exception {
    const ERROR_POOL_NAME_EMPTY = 1;
    public $ERROR_SET           = array(
        self::ERROR_POOL_NAME_EMPTY => array(
            'code'    => self::ERROR_POOL_NAME_EMPTY,
            'message' => 'redis pool name empty'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}
