-- 首先继承Controller基类
local Sso_Controller = Controller:new()
local sm = require 'Sso/Models/Sample'
local json = require 'Json'
function Sso_Controller:indexAction()
    -- var_dump(json.encode({1, 2, 3, {x = 10}}))
    sm:new()
    return true
end
return Sso_Controller
