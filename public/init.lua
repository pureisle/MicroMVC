-- 初始化
local string_sub = string.sub
local debug_getinfo = debug.getinfo
local string_len = string.len
FRAMEWORK = {
    INIT_FILE = 'init.lua',
    PATH = {
        library = 'Libraries',
        config = 'config',
        test = 'Tests',
        controller = 'Controllers',
        view = 'Views',
        daemon = 'Daemons',
        plugin = 'Plugins',
        cache = 'Cache',
        data = 'Data',
        entity = 'Entities',
        model = 'Models'
    },
    DIRECTORY_SEPARATOR = '/',
    IS_CLI = false,
    LOG_ROOT_PATH = '/tmp', -- 日志根目录
    CURRENT_ENV_NAME = 'dev', --当前环境默认名称
    MODULE_PREFIX = 'lua_',
    NGX_CACHE_KEY = 'micromvc_cache',
    ROOT_PATH = nil,
    FRAMEWORK_ROOT = nil
}
FRAMEWORK.ROOT_PATH = string_sub(debug_getinfo(1, "S").source:sub(2), 1, -string_len('/public/'..FRAMEWORK.INIT_FILE) - 1)
FRAMEWORK.FRAMEWORK_ROOT = FRAMEWORK.ROOT_PATH.."/Framework/Luas"
package.path = FRAMEWORK.FRAMEWORK_ROOT.."/?.lua;"..FRAMEWORK.FRAMEWORK_ROOT.."/Libraries/?.lua;"..FRAMEWORK.ROOT_PATH.."/?.lua;"..'?.lua;'..package.path
require "GlobalFunction" --加载自定义公共库
require 'Libraries.Class'
require 'Tools'
require 'Controller'