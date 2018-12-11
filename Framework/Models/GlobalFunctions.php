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