--[[
-- Controller基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Controller = Class:new('Controller')
-- 构造方法
function Controller:new ()
    return self
end
function Controller:classCheck()
    return 'Controller'
end

return Controller
