<?php
/**
 * 有限状态机状态基类
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class FiniteState {
    protected $_fsm      = null;
    private $_next_state = null;
    public function __construct(FiniteStateMachine $fsm, int $next_state = null) {
        $this->_fsm        = $fsm;
        $this->_next_state = $next_state;
    }
    /**
     * 进入状态时调用，可覆盖
     */
    public function onStateEnter($from_state) {
    }
    /**
     * 每次动作调用,必须实现的抽象方法
     */
    abstract public function onStateTick($param = null);
    /**
     * 退出状态时调用，可覆盖
     */
    public function onStateExit() {}
    /**
     * 获取预设的下个状态值
     * @return int
     */
    public function getNextState() {
        return $this->_next_state;
    }
    /**
     * 执行状态转移
     * @param    int $state
     * @return
     */
    public function trans(int $state) {
        return $this->_fsm->trans($state);
    }
    /**
     * 设置状态机数据
     * @param mix $data
     */
    public function setData($data) {
        return $this->_fsm->setData($data);
    }
    /**
     * 获取状态机数据
     * @return mix
     */
    public function getData() {
        return $this->_fsm->getData();
    }
    /**
     * 终止状态机执行
     */
    public function fsmStop() {
        $this->_fsm->stop();
    }
}