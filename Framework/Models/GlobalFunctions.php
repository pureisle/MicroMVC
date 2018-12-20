<?php
/**
 * 触发hook trigger
 * @param    string $key
 * @param    ...
 * @return
 */
function hook_trigger(string $key) {
    $args = func_get_args();
    array_shift($args);
    \Framework\Libraries\HookManager::$GLOBAL_HOOKS->trigger($key, ...$args);
}
/**
 * 获取全局单例
 * @param  string   $class_name
 * @return object
 */
function single_instance($class_name) {
    $args = func_get_args();
    array_shift($args);
    return \Framework\Libraries\SingletonManager::$SINGLETON_POOL->getInstance($class_name, ...$args);
}
/**
 * 安全退出
 * 需要使用php的exit()时，建议都使用该方法退出程序。
 * 以防框架无法完成后续流程。如果使用者清楚自己的目的，依然可以继续使用系统exit()
 */
function safe_exit() {
    throw new \Framework\Models\ExitException();
}