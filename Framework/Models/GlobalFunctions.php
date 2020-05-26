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
/**
 * 多语言转换
 * @param  string   $str
 * @param  mix      $params sprintf的参数列表
 * @return string
 */
function _($str) {
    $args = func_get_args();
    array_shift($args);
    $module           = \Framework\Libraries\Tools::getModule();
    $lang             = \Framework\Libraries\Tools::getLang();
    static $i18n_conf = null; //进程内缓存
    if ( ! isset($i18n_conf[$module][$lang])) {
        $yac = new \Yac($module);
        $tmp = $yac->get($lang);
        if (empty($tmp)) {
            $i18n_conf[$module][$lang] = \Framework\Libraries\ConfigTool::loadByName('i18n.' . $lang, $module, \Framework\Libraries\ConfigTool::INI_SUFFIX);
            //共享内存缓存,这里有30s的缓存
            $sr = $yac->set($lang, $i18n_conf[$module][$lang], 30);
        } else {
            $i18n_conf[$module][$lang] = $tmp;
        }
    }
    if (isset($i18n_conf[$module][$lang][$str])) {
        $str = $i18n_conf[$module][$lang][$str];
    }
    return sprintf($str, ...$args);
}