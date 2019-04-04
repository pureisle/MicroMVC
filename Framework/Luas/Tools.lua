--[[
-- 框架工具类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Tools = Class:new('Tools')
Tools.ENV_DEV = 'dev' --开发环境
Tools.ENV_PRO = 'pro' --生产环境
Tools.ENV_INDEX_NAME = 'CURRENT_ENV_NAME'
Tools.env_name = ''
-- 获取代码执行环境
function Tools:getEnv()
    --生产环境禁止变更设置
    if (CURRENT_ENV_NAME == self.ENV_PRO) then
        return self.ENV_PRO
    end
    local cookie = require 'Cookie'
    cookie = cookie:new()
    local env
    if empty(self.env_name) == false then
        env = self.env_name
    elseif not empty(cookie[self.ENV_INDEX_NAME]) then
        env = cookie[self.ENV_INDEX_NAME]
    else
        env = CURRENT_ENV_NAME
    end
    return env;
end
-- 设置代码执行环境
function Tools:setEnv(env)
    self.env_name = env;
    if not self:isCli() then
        local cookie = require 'Cookie'
        cookie = cookie:new()
        local host = ngx.req.get_headers()['host']
        local ok, err = cookie:set({key = self.ENV_INDEX_NAME, value = env, domain = host, expires = os.time() + 3600})
    end
end
-- 是否为cli方式运行
function Tools:isCli()
    if (type(IS_CLI) == 'boolean') then
        return IS_CLI
    end
    return false;
end
return Tools
