--[[
-- Controller基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Controller = {
}
-- 构造方法
function Controller:new ()
    o = {}
    setmetatable(o, {__index = self})
    return o
end
function Controller:classCheck()
    return 'Controller'
end

return Controller
