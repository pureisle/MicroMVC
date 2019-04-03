--[[
-- 入口文件
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
__FILE__ = 'index.lua'
REQUEST_URI = ngx.var.request_uri
ROOT_PATH = string.sub(debug.getinfo(1, 'S').short_src, 0, -string.len(__FILE__) - 9)
FRAMEWORK = ROOT_PATH.."/Framework"
CONFIG_PACKAGE_PATH = package.path
package.path = FRAMEWORK.."/Luas/?.lua;"..ROOT_PATH.."/?.lua;"..CONFIG_PACKAGE_PATH
MODULE_PREFIX = 'lua_'
require "GlobalFunction" --加载自定义公共库
Application = require "Application"
local app = Application:new(REQUEST_URI)
app:run()