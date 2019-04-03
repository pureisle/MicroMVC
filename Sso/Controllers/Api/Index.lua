-- 首先继承Controller基类
local Sso_Controller = Controller:new()
function Sso_Controller:indexAction()
    var_dump(package.path)
    local json = require 'Json'
    var_dump(json.encode({1, 2, 3, {x = 10}}))
    return 'sso api indexAction'
end
return Sso_Controller
