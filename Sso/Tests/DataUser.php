<?php
namespace Sso\Tests;
use Framework\Libraries\TestSuite;
use Sso\Data\User;

class DataUser extends TestSuite {
    private $_user      = null;
    private $_user_data = array(
        array(
            'name'   => 'name1',
            'email'  => '',
            'tel'    => '188888888',
            'extend' => array('nick_name' => 'pure1')
        ), array(
            'name'   => 'name2',
            'email'  => '',
            'tel'    => '',
            'extend' => array('nick_name' => 'pure2')
        ), array(
            'name'   => 'name3',
            'email'  => 'asdf@dddd.com',
            'tel'    => '',
            'extend' => array('nick_name' => 'pure3')
        )
    );
    public function beginTest() {
        $this->_user = new User();
    }
    public function testAddUser() {
        foreach ($this->_user_data as $user_info) {
            $name   = $user_info['name'];
            $email  = $user_info['email'];
            $tel    = $user_info['tel'];
            $extend = $user_info['extend'];
            // $ret    = $this->_user->addUser($name, $email, $tel, $extend);
            // $this->assertEq($ret, 1);
        }
    }
    public function testGetListInfo() {
        $ret = $this->_user->getListInfo();
        // var_dump($ret);
    }
    public function testUpdateByUid() {
        // $user_list = $this->_user->getListInfo();
        // var_dump($user_list);
        // $ret       = $this->_user->updateByUid($user_list[0]['uid'], '', null,'',array('te'=>'1','sfe'=>'2'));
        // $user_list = $this->_user->getListInfo();
        // var_dump($ret, $user_list);
    }
    public function testRemoveByUid(){
        // $ret       = $this->_user->removeByUid('10');
        // var_dump($ret);
    }
}