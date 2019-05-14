-- 首先继承Controller基类
local Sso_Controller = Controller:new()
function Sso_Controller:indexAction()
    -- Profiler = require 'Profiler'
    -- Profiler:setGetTimeMethod(function () return microtime(true) end)
    -- Profiler:start()
    -- local Http = require "resty.http"
    -- local http = Http.new()
    -- local res, err = http:request_uri("http://example.com/helloworld", {
    --     method = "POST",
    --     body = "a=1&b=2",
    --     headers = {
    --         ["Content-Type"] = "application/x-www-form-urlencoded",
    --     },
    --     keepalive_timeout = 60,
    --     keepalive_pool = 10
    -- })
    -- var_dump(res,err)
    -- var_dump(package.path);
    -- local ConfigTool = require 'ConfigTool'
    -- var_dump(ConfigTool:loadByName('redis','Sso'))
    ngx.say('hello,world')
    -- Tools:require('Test')
    -- local sm=  Tools:require('Sso.Models.Sample')
    -- sm:new()
    -- var_dump(self,self.parent)
    -- var_dump(self:getPostParams())
    -- local Redis = require 'resty.redis'
    -- var_dump(package.loaded)
    -- for i = 1, 1 do
    -- local rt = Redis:new()
    -- rt:connect('redis:test', 'Sso')
    -- local reuse = rt:get_reused_times()
    -- var_dump(rt:set('test_key','test_value'))
    -- var_dump(rt:hgetall('Vote\\Data:Vote:Count:2019_172'))
    -- local times, er = rt:set_keepalive(600, 100)
    -- ngx.say(times, '--', reuse)
    -- end
    -- sm:new()

    -- Profiler:stop()
    -- Profiler:writeReport("/tmp/profile.txt")
    return true
end
return Sso_Controller
