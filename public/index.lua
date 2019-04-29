--[[
-- 入口文件
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
FRAMEWORK = {
    __FILE__ = 'index.lua',
    PATH = {
        library = 'Libraries',
        config = nil,
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
    CONFIG_FOLDER = 'config/lua_config',
    IS_CLI = false,
    LOG_ROOT_PATH = '/tmp', -- 日志根目录
    CURRENT_ENV_NAME = ngx.var.CURRENT_ENV_NAME or 'pro', --当前环境名称
    MODULE_PREFIX = 'lua_',
    NGX_CACHE_KEY = 'micromvc_cache',
    ROOT_PATH = nil,
    FRAMEWORK_ROOT = nil
}
FRAMEWORK.ROOT_PATH = string.sub(debug.getinfo(1, "S").source:sub(2), 1, -string.len('/public/'..FRAMEWORK.__FILE__) - 1)
FRAMEWORK.FRAMEWORK_ROOT = FRAMEWORK.ROOT_PATH.."/Framework"
package.path = FRAMEWORK.FRAMEWORK_ROOT.."/Luas/?.lua;"..FRAMEWORK.ROOT_PATH.."/?.lua;"..package.path
require "GlobalFunction" --加载自定义公共库
local Application = require "Application"
local app = Application:new(ngx.var.request_uri)
app:run()
