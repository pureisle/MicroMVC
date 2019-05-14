--[[
-- 入口文件
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
FRAMEWORK.CURRENT_ENV_NAME = ngx.var.CURRENT_ENV_NAME --读取nginx配置的运行环境变量
local Application = require(FRAMEWORK.FRAMEWORK_ROOT.."/Application")
local app = Application:new(ngx.var.request_uri)
app:run()
