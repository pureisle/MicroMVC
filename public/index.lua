--[[
-- 入口文件
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
FRAMEWORK = {
    __FILE__ = 'index.lua',
    REQUEST_URI = ngx.var.request_uri,
    DIRECTORY_SEPARATOR = '/',
    CONFIG_FOLDER = 'config/lua_config',
    IS_CLI = false,
    CURRENT_ENV_NAME = 'dev', --当前环境名称
    MODULE_PREFIX = 'lua_',
    NGX_CACHE_KEY = 'micromvc_cache'
}
FRAMEWORK.ROOT_PATH = string.sub(debug.getinfo(1, 'S').short_src, 0, -string.len(FRAMEWORK.__FILE__) - 9)
FRAMEWORK.FRAMEWORK_ROOT = FRAMEWORK.ROOT_PATH.."/Framework"
package.path = FRAMEWORK.FRAMEWORK_ROOT.."/Luas/?.lua;"..FRAMEWORK.ROOT_PATH.."/?.lua;"..package.path
require "GlobalFunction" --加载自定义公共库
local Application = require "Application"
local app = Application:new(FRAMEWORK.REQUEST_URI)
app:run()
