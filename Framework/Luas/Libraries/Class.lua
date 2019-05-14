--[[
-- Class基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local error = error
local type = type
local setmetatable = setmetatable
Class = {}
-- 构造方法
function Class:new (class_name, parent, ...)
    if class_name == nil then
        error("must provide a class name!", 2)
    end
    if type(class_name) ~= 'string' then
        error("class name should be a string!", 2)
    end
    local o = {
        class = class_name,
    }
    if parent then
        p = {__index = parent}
        o.parent = parent
    else
        p = {
            __index = function (table, key)
                return function () error('index: '..key..' undefined') end
            end,
            __tostring = function () return 'object' end
        }
        o.parent = p
    end
    setmetatable(o, p)
    return o
end
function Class:type(class_obj)
    return class_obj.class
end
