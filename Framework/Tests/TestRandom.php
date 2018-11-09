<?php
/**
 * 生产随机数测试
 *
 * Loop num : 2000
 * uniqid('',true) : 2.5 ms
 * uniqid('',false) : 112.3 ms
 * openssl_random_pseudo_bytes(14) : 4.3 ms
 * mt_rand(100000000000000, 999999999999999) : 0.7 ms
 * random_int(100000000000000, 999999999999999) : 5.5 ms
 * random_bytes(14) : 6.7 ms
 * shuffle : 3.6 ms
 * self : 16.5 ms
 * mcrypt_create_iv(MCRYPT_DEV_RANDOM) : 295.3 ms
 * mcrypt_create_iv(MCRYPT_DEV_URANDOM) : 7.2 ms
 * mcrypt_create_iv(MCRYPT_RAND) : 1.5 ms
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\RunTime;
use Framework\Libraries\TestSuite;

class TestRandom extends TestSuite {
    const LOOP_NUM = 2000;
    private $_ret  = 'Loop num : ' . self::LOOP_NUM . "\n";
    public function endTest() {
        echo $this->_ret;
    }
    /**
     * 测试 uniqid 第二个参数为true , ret = 5b3f2c44c3d293.72030857 , 伪随机
     */
    public function testUniqidTrue() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = uniqid('', true);
        }
        RunTime::stop();
        $this->_ret .= "uniqid('',true) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 uniqid 第二个参数为false , ret = 5b3f2c2b2be23 , 伪随机
     */
    public function testUniqidFalse() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = uniqid('', false);
        }
        RunTime::stop();
        $this->_ret .= "uniqid('',false) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 openssl_random_pseudo_bytes  ,ret = K$HÑN«ÙDx , 真随机
     */
    public function testOpensslRandomPeseudoBytes() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = openssl_random_pseudo_bytes(14);
        }
        RunTime::stop();
        $this->_ret .= "openssl_random_pseudo_bytes(14) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 mt_rand  ,ret = 672242490993812 , 伪随机
     *  As of PHP 7.1.0, rand() uses the same random number generator as mt_rand().
     */
    public function testmtRand() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = mt_rand(100000000000000, 999999999999999);
        }
        RunTime::stop();
        $this->_ret .= "mt_rand(100000000000000, 999999999999999) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 random_int  ,ret = 672242490993812 , 伪随机
     */
    public function testrandom_int() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = random_int(100000000000000, 999999999999999);
        }
        RunTime::stop();
        $this->_ret .= "random_int(100000000000000, 999999999999999) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 random_int  ,ret = Ñ)bZ)Êûm¥ , 伪随机
     */
    public function testrandom_bytes() {
        RunTime::start();
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = random_bytes(14);
        }
        RunTime::stop();
        $this->_ret .= "random_bytes(14) : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 shuffle
     */
    public function testshuffle() {
        RunTime::start();
        $range = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = shuffle($range);
        }
        RunTime::stop();
        $this->_ret .= "shuffle : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 self
     */
    public function testSelf() {
        $pattern  = '97~YdPM,z4OtEWU(ruBa?G[_N/.w>c=O;0m^%HCh}FskAR$+VQyloveLjn:)iq]@ZS2#DXpgJ!\5T81I3-6fx{b<K*';
        $salt_len = 14;
        RunTime::start();
        $range = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        $ret   = '';
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            for ($j = 0; $j < $salt_len; $j++) {
                $index = mt_rand(0, strlen($pattern) - 1);
                $tmp   = $pattern[$index];
                $ret .= $tmp;
            }
        }
        RunTime::stop();
        $this->_ret .= "self : " . RunTime::spent() . " ms\n";
    }
    /**
     * 测试 mcrypt_create_iv  ret = f¤ôNhA´¢ƭ
     * The source of the IV. The source can be MCRYPT_RAND (system random number generator), MCRYPT_DEV_RANDOM (read data from /dev/random) and MCRYPT_DEV_URANDOM (read data from /dev/urandom). Prior to 5.3.0, MCRYPT_RAND was the only one supported on Windows.
     */
    public function testmcrypt_create_iv() {
        RunTime::start();
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
        }
        RunTime::stop();
        $this->_ret .= "mcrypt_create_iv(MCRYPT_DEV_RANDOM) : " . RunTime::spent() . " ms\n";
    }
    public function testmcrypt_create_iv1() {
        RunTime::start();
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
        }
        RunTime::stop();
        $this->_ret .= "mcrypt_create_iv(MCRYPT_DEV_URANDOM) : " . RunTime::spent() . " ms\n";
    }
    public function testmcrypt_create_iv2() {
        RunTime::start();
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        for ($i = 0; $i < self::LOOP_NUM; $i++) {
            $ret = mcrypt_create_iv($size, MCRYPT_RAND);
        }
        RunTime::stop();
        $this->_ret .= "mcrypt_create_iv(MCRYPT_RAND) : " . RunTime::spent() . " ms\n";
    }
}