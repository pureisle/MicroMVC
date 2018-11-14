<?php
/**
 * 有限状态机类单元测试
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Tests;
use Framework\Libraries\FiniteState;
use Framework\Libraries\FiniteStateMachine;
use Framework\Libraries\TestSuite;

class TestFiniteStateMachine extends TestSuite {
    const STATE_A    = 1;
    const STATE_B    = 2;
    const STATE_C    = 3;
    const TICK_COUNT = 5;
    private $_fsm    = null;
    public function beginTest() {
        $this->_fsm = new FiniteStateMachine();
    }
    public function testRegister() {
        $state_a = new StateA($this->_fsm, self::STATE_B); //传入第二个构造参数时，如果tick()实现方法返回true则自动转移状态
        $this->_fsm->register(self::STATE_A, $state_a);
        $state_b = new StateB($this->_fsm);
        $this->_fsm->register(self::STATE_B, $state_b);
        $state_c = new StateC($this->_fsm);
        $this->_fsm->register(self::STATE_C, $state_c);
    }
    public function testSetInitState() {
        $this->_fsm->setInitState(self::STATE_A);
    }
    public function testTick() {
        $this->_fsm->run();
        $state_list = $this->_fsm->getStateList();
        foreach ($state_list as $key => $value) {
            $this->assertEq($value->_is_enter, true);
            $this->assertEq($value->_is_exit, true);
            $this->assertEq($value->_tick_count, self::TICK_COUNT);
        }
    }
}

class StateA extends FiniteState {
    public $_tick_count = 0;
    public $_is_enter   = false;
    public $_is_exit    = false;
    public function onStateEnter() {
        $this->_is_enter = true;
    }
    public function onStateExit() {
        $this->_is_exit = true;
    }
    public function onStateTick() {
        $this->_tick_count++;
        if ($this->_tick_count >= TestFiniteStateMachine::TICK_COUNT) {
            return true;
        }
        return false;
    }
}
class StateB extends FiniteState {
    public $_tick_count = 0;
    public $_is_enter   = false;
    public $_is_exit    = false;
    public function onStateEnter() {
        $this->_is_enter = true;
    }
    public function onStateExit() {
        $this->_is_exit = true;
    }
    public function onStateTick() {
        $this->_tick_count++;
        if ($this->_tick_count >= TestFiniteStateMachine::TICK_COUNT) {
            $this->trans(TestFiniteStateMachine::STATE_C);
        }
    }
}
class StateC extends FiniteState {
    public $_tick_count = 0;
    public $_is_enter   = false;
    public $_is_exit    = false;
    public function onStateEnter() {
        $this->_is_enter = true;
    }
    public function onStateExit() {
        $this->_is_exit = true;
    }
    public function onStateTick() {
        $this->_tick_count++;
        if ($this->_tick_count >= TestFiniteStateMachine::TICK_COUNT) {
            $this->fsmStop();
        }
    }
}