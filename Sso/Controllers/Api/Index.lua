-- 首先继承Controller基类
local Sso_Controller = Controller:new()
function Sso_Controller:indexAction()
    return 'sso api indexAction'
end
return Sso_Controller
