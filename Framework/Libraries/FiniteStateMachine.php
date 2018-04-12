<?php
/**
 * 有限状态机管理类
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class FiniteStateMachine {
    private $_current_state = null;
    private $_state_objs    = array();
    private $_state_set     = array();
    /**
     * 注册新的状态
     * @param    int   $state
     * @param    State $state_obj
     * @return
     */
    public function register(int $state, FiniteState $state_obj) {
        $this->_state_objs[$state] = $state_obj;
        $this->_state_set[$state]  = true;
        return $this;
    }
    /**
     * 状态转移
     * @param    int $state
     * @return
     */
    public function trans(int $state) {
        $this->_state_objs[$this->_current_state]->OnStateExit();
        $this->_state_objs[$state]->OnStateEnter();
        $this->_current_state = $state;
        return $this;
    }
    /**
     * 设置初始状态
     * @param int $state
     */
    public function setInitState(int $state) {
        $this->_current_state = $state;
        return $this;
    }
    /**
     * 每次动作调用
     * @return
     */
    public function tick() {
        $ret = $this->_state_objs[$this->_current_state]->OnStateTick();
        return $ret;
    }
}