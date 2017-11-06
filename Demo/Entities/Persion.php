<?php
/**
 * 测试model
 */
namespace Demo\Entities;

class Persion {
    private $_id   = null;
    private $_name = null;
    public function __construct($id, $name) {
        $this->_id   = $id;
        $this->_name = $name;
    }
    public function getId() {
        return $this->_id;
    }
    public function getName() {
        return $this->_name;
    }
}