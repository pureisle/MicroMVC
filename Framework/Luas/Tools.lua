--[[
-- 框架工具类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local cookie = require 'resty.cookie'
local type = type
local debug_getinfo = debug.getinfo
local dirname = dirname
local string_gsub = string.gsub
local os_time = os.time
Tools = Class:new('Tools')
Tools.ENV_DEV = 'dev' --开发环境
Tools.ENV_PRO = 'pro' --生产环境
Tools.ENV_INDEX_NAME = 'CURRENT_ENV_NAME'
Tools.env_name = ''
-- 获取代码执行环境
function Tools:getEnv()
    --生产环境禁止变更设置
    if (FRAMEWORK.CURRENT_ENV_NAME == self.ENV_PRO) then
        return self.ENV_PRO
    end
    cookie = cookie:new()
    local env
    if empty(self.env_name) == false then
        env = self.env_name
    elseif not empty(cookie[self.ENV_INDEX_NAME]) then
        env = cookie[self.ENV_INDEX_NAME]
    else
        env = FRAMEWORK.CURRENT_ENV_NAME
    end
    return env;
end
-- 设置代码执行环境
function Tools:setEnv(env)
    self.env_name = env;
    if not self:isCli() then
        cookie = cookie:new()
        local host = ngx.req.get_headers()['host']
        local ok, err = cookie:set({key = self.ENV_INDEX_NAME, value = env, domain = host, expires = os_time() + 3600})
    end
end
-- 是否为cli方式运行
function Tools:isCli()
    if (type(FRAMEWORK.IS_CLI) == 'boolean') then
        return FRAMEWORK.IS_CLI
    end
    return false;
end
-- 可以根据相对路径加载module包
function Tools:require(module_name)
    local path = dirname(debug_getinfo(2, "S").source:sub(2))
    local module_path = string_gsub(module_name, '[.]', '/')
    local real_path = path..'/'..module_path
    if(not empty(package.loaded[real_path]) or file_exists(real_path..'.lua')) then
        return require(real_path)
    else
        return require(module_name)
    end
end
return Tools
