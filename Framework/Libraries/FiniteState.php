<?php
/**
 * 有限状态机状态基类
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
abstract class FiniteState {
    protected $_fsm;
    public function __construct(FiniteStateMachine $fsm) {
        $this->_fsm = $fsm;
    }
    /**
     * 进入状态时调用
     */
    abstract public function OnStateEnter();
    /**
     * 每次动作调用
     */
    abstract public function OnStateTick();
    /**
     * 退出状态时调用
     */
    abstract public function OnStateExit();
}