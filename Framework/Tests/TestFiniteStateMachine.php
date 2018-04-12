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
    const STATE_A = 1;
    const STATE_B = 2;
    const STATE_C = 3;
    private $_fsm = null;
    public function beginTest() {
        $this->_fsm = new FiniteStateMachine();
    }
    public function testRegister() {
        $state_a = new StateA($this->_fsm);
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
        while (true) {
            $ret = $this->_fsm->tick();
            if (false === $ret) {
                break;
            }
        }
    }
}

class StateA extends FiniteState {
    private $_tick_count = 0;
    public function onStateEnter() {
        // echo "StateA->onStateEnter()\n";
    }
    public function onStateExit() {
        // echo "StateA->onStateExit()\n";
    }
    public function onStateTick() {
        // echo "StateA->onStateTick()\n";
        $this->_tick_count++;
        if ($this->_tick_count >= 5) {
            $this->_fsm->trans(TestFiniteStateMachine::STATE_B);
        }
    }
}
class StateB extends FiniteState {
    private $_tick_count = 0;
    public function onStateEnter() {
        // echo "StateB->onStateEnter()\n";
    }
    public function onStateExit() {
        // echo "StateB->onStateExit()\n";
    }
    public function onStateTick() {
        // echo "StateB->onStateTick()\n";
        $this->_tick_count++;
        if ($this->_tick_count >= 5) {
            $this->_fsm->trans(TestFiniteStateMachine::STATE_C);
        }
    }
}
class StateC extends FiniteState {
    private $_tick_count = 0;
    public function onStateEnter() {
        // echo "StateC->onStateEnter()\n";
    }
    public function onStateExit() {
        // echo "StateC->onStateExit()\n";
    }
    public function onStateTick() {
        // echo "StateC->onStateTick()\n";
        $this->_tick_count++;
        if ($this->_tick_count >= 5) {
            return false;
        }
    }
}
