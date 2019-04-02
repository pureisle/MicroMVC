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
require "Application"
local app = Application:new(REQUEST_URI)
app:run()

-- Redis = require "Redis"
-- red = Redis:new();
-- red:set_timeout(1000)

-- for k, v in pairs(ngx.req.get_headers()) do
--     ngx.say(k, ": ", v)
-- end

--get请求uri参数
-- ngx.say("uri args begin", "<br/>")
-- local uri_args = ngx.req.get_uri_args()
-- for k, v in pairs(uri_args) do
--     if type(v) == "table" then
--         ngx.say(k, " : ", table.concat(v, ", "), "<br/>")
--     else
--         ngx.say(k, ": ", v, "<br/>")
--     end
-- end
-- ngx.say("uri args end", "<br/>")
-- ngx.say("<br/>")

--post请求参数
-- ngx.req.read_body()
-- ngx.say("post args begin", "<br/>")
-- local post_args = ngx.req.get_post_args()
-- for k, v in pairs(post_args) do
--     if type(v) == "table" then
--         ngx.say(k, " : ", table.concat(v, ", "), "<br/>")
--     else
--         ngx.say(k, ": ", v, "<br/>")
--     end
-- end
-- ngx.say("post args end", "<br/>")
-- ngx.say("<br/>")
