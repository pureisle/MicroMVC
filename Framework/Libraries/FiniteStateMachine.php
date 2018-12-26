<?php
/**
 * 有限状态机管理类
 * 用法:
 * 必须先注册所有状态,如:
 *     $FiniteStateMachine_OBJ->register(STATE_A, new FiniteState($FiniteStateMachine_OBJ));
 * 可以自行控制tick,适合每次tick后有额外业务逻辑的情况下使用:
 *     //TO DO : 初始状态运行 onStateEnter 方法
 *     while (true) {
 *         $ret = $FiniteStateMachine_OBJ->tick();
 *         //TO DO : 额外业务逻辑和退出条件
 *
 *     }
 *     //TO DO : 退出状态机执行最后一个状态的 onStateExit 方法
 *
 * 也可以直接运行：
 *     $FiniteStateMachine_OBJ->run();
 * 状态机停止时机为停止方法调用后：
 *     FiniteState->fsmStop()；
 *
 * 在运行状态机 run 方法情况下，如果 FiniteState 实例化时传入第二个状态值参数，
 * 则在状态 FiniteState->tick() 返回结果为 true 时，自动进行状态转移。
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
namespace Framework\Libraries;
class FiniteStateMachine {
    private $_current_state      = null;
    private $_state_objs         = array();
    private $_state_set          = array();
    private $_tick_list          = array();
    private $_is_use_tick_params = false;
    private $_is_stop            = false;
    private $_data               = array();
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
     * 获取已注册的状态对象列表
     * @return array
     */
    public function getStateList() {
        return $this->_state_objs;
    }
    /**
     * 状态转移
     * @param    int $state
     * @return
     */
    public function trans(int $state) {
        if ( ! isset($this->_state_objs[$state])) {
            throw new FiniteStateMachineException(FiniteStateMachineException::STATE_OBJ_EMPTY);
        }
        $this->_state_objs[$this->_current_state]->onStateExit();
        $this->_state_objs[$state]->onStateEnter($this->_current_state);
        $this->_current_state = $state;
        return $this;
    }
    /**
     * 设置初始状态
     * @param int $state
     */
    public function setInitState(int $state) {
        if ( ! isset($this->_state_objs[$state])) {
            throw new FiniteStateMachineException(FiniteStateMachineException::STATE_OBJ_EMPTY);
        }
        $this->_current_state = $state;
        return $this;
    }
    /**
     * 设置特殊的tick时钟数据,每次tick的数据会按该数组内数据进行，如果tick数据循环完毕则强制终止,
     * 禁止tick数据的值为 false ,这是获取数据终止条件
     * @param array $data
     */
    public function setTickData(array $data) {
        if (empty($data)) {
            return true;
        }
        $this->_tick_list          = $data;
        $this->_is_use_tick_params = true;
        return false;
    }
    /**
     * 每次动作调用
     * @return
     */
    public function tick() {
        $param = null;
        if ($this->_is_use_tick_params) {
            $param = current($this->_tick_list);
            if (false === $param) {
                $this->stop();
                return;
            }
            next($this->_tick_list);
        }
        $ret = $this->_state_objs[$this->_current_state]->onStateTick($param);
        return $ret;
    }
    /**
     * 停止状态机
     */
    public function stop() {
        $this->_is_stop = true;
    }
    /**
     * 状态机执行入口
     */
    public function run() {
        if (empty($this->_state_objs[$this->_current_state])) {
            return;
        }
        $this->_state_objs[$this->_current_state]->onStateEnter(false);
        while (false === $this->_is_stop) {
            $ret = $this->tick();
            if ($ret && $this->_state_objs[$this->_current_state]->getNextState() !== null) {
                $this->trans($this->_state_objs[$this->_current_state]->getNextState());
            }
        }
        $this->_state_objs[$this->_current_state]->onStateExit();
    }
    /**
     * 设置状态机数据
     * @param string $key
     * @param mix    $data
     */
    public function setData($data) {
        $this->_data = $data;
        return true;
    }
    /**
     * 获取状态机数据
     * @param  string $key
     * @return mix
     */
    public function getData() {
        return $this->_data;
    }
}

class FiniteStateMachineException extends Exception {
    const STATE_OBJ_EMPTY = 1;
    public $ERROR_SET     = array(
        self::STATE_OBJ_EMPTY => array(
            'code'    => self::STATE_OBJ_EMPTY,
            'message' => 'state index is empty'
        )
    );
    public function __construct($code = 0) {
        parent::__construct($code);
    }
}