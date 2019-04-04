-- 首先继承Controller基类
local Sso_Controller = Controller:new()
local sm = require 'Sso/Models/Sample'
local Json = require 'Json'
local Redis = require 'Redis'
function Sso_Controller:indexAction()
    -- var_dump(Json.encode({1, 2, 3, {x = 10}}))

    red = Redis:new()
    -- Tools:setEnv('dev')
    -- var_dump(Tools:getEnv())
    -- var_dump(implode('1,1', explode( '..','asdf.sdf..sadf..wfe.f2f', 3)))
    -- var_dump(ConfigTool:getFilePath('redis', 'Sso'))
    -- var_dump(ConfigTool:getConfig('redis', 'Sso'))
    var_dump(ConfigTool:loadByName('redis:session_read', 'Sso'))
    -- var_dump(ngx.req.get_headers()['cookie'])
    -- sm:new()
    return true
end
return Sso_Controller
