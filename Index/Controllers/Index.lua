-- 首先继承Controller基类
local Index_Controller = Controller:new()
function Index_Controller:indexAction()
    -- ngx.say('Hello,MicroMVC')
    self:assign({text = 'Hello,MicroMVC'})
    return true
end
return Index_Controller
