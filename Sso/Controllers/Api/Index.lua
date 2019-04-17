-- 首先继承Controller基类
local Sso_Controller = Controller:new()
function Sso_Controller:indexAction()
    -- local Json = require 'Json'
    -- var_dump(Json.encode({1, 2, 3, {x = 10}}))
    Profiler = require 'Profiler'
    Profiler:setGetTimeMethod(function () return microtime(true) end)
    Profiler:start()

    local Redis = require 'Redis'
    local connect_time
    local RedisTool = require 'RedisTool'
    for i = 1, 5 do
        rt = RedisTool:new('redis:session', 'Sso')
        local reuse = rt:get_reused_times()
        -- var_dump(rt:set('test_key','test_value'))
        var_dump(rt:get('test_key'))
        local times, er = rt:set_keepalive(600, 100)
        ngx.say(times, '--', reuse)
    end
    local sm = require 'Sso/Models/Sample'
    -- sm:new()

    Profiler:stop()
    Profiler:writeReport("/tmp/profile.txt")
    return true
end
return Sso_Controller
