--[[
-- Class基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Class = {}
-- 构造方法
function Class:new (class_name, parent, ...)
    if class_name == nil then
        error("must provide a class name!", 2)
    end
    if type(class_name) ~= 'string' then
        error("class name should be a string!", 2)
    end
    o = {
        class = class_name,
    }
    if parent then
        parent.__index = parent
        o.parent = parent
    else
        o.parent = self
        parent = {
            __index = self,
            __tostring = function () return 'object' end
        }
    end
    setmetatable(o, parent)
    return o
end
function Class:type(class_obj)
    return class_obj.class
end
