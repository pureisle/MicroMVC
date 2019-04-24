-- 首先继承Controller基类
local Sso_Controller = Controller:new()
local sm = require 'Sso/Models/Sample'
function Sso_Controller:indexAction()
    -- Profiler = require 'Profiler'
    -- Profiler:setGetTimeMethod(function () return microtime(true) end)
    -- Profiler:start()
    
    local Redis = require 'Redis'
    -- var_dump(package.loaded)
    for i = 1, 1 do
        local rt = Redis:new()
        rt:connect('redis:test', 'Sso')
        local reuse = rt:get_reused_times()
        -- var_dump(rt:set('test_key','test_value'))
        var_dump(rt:hgetall('Vote\\Data:Vote:Count:2019_172'))
        local times, er = rt:set_keepalive(600, 100)
        -- ngx.say(times, '--', reuse)
    end
    sm:new()

    -- Profiler:stop()
    -- Profiler:writeReport("/tmp/profile.txt")
    return true
end
return Sso_Controller
