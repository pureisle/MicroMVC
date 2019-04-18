--[[
-- MVC类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
require 'Class'
require 'Tools'
local Application = Class:new('Application')
-- 构造方法
function Application:new (uri)
    self.uri = uri
    return self
end
function Application:run()
    local router_info = self:router()
    local require_path = router_info['module'] .. '/Controllers/'..router_info['controller']
    local error_handler = self.errorHandler
    require 'Controller'
    local controller = self:autoLoad(require_path)
    xpcall(function ()
        local c_name = controller.classCheck()--检验是否继承父类
    end, error_handler)
    xpcall(function ()
        local c_ret = controller[router_info['action'] .. 'Action']()
    end, error_handler)
end
function Application:errorHandler(msg)
    var_dump(msg)
    var_dump(debug.traceback())
end
function Application:autoLoad(file)
    local ok, c_obj = pcall(require, file)
    if not ok then
        var_dump(c_obj)
    end
    return c_obj
end
function Application:router()
    local tmp = {}
    local i = 1
    local uri = self.uri
    local q_mark = string.find(uri, '?')
    if(q_mark ~= nil)then
        uri = string.sub(uri, 1, q_mark - 1)
    end
    for k in string.gmatch(uri, "([^?/]*)") do
        if(string.len(k) > 0)then
            tmp[i] = k
            i = i + 1
        end
    end
    local tmp_count = #(tmp)
    local ret = {}
    if(tmp_count == 0)
        then
        ret['module'] = 'Index'
        ret['controller'] = 'Index'
        ret['action'] = 'index'
    elseif(tmp_count == 1)
        then
        ret['module'] = tmp[1]
        ret['controller'] = 'Index'
        ret['action'] = 'index'
    elseif(tmp_count == 2)
        then
        ret['module'] = tmp[1]
        ret['controller'] = ucfirst(tmp[2])
        ret['action'] = 'index'
    elseif(tmp_count == 3)
        then
        ret['module'] = tmp[1]
        ret['controller'] = ucfirst(tmp[2])
        ret['action'] = tmp[3]
    else
        ret['module'] = tmp[1]
        ret['controller'] = ucfirst(tmp[2])
        for i = 3, tmp_count - 1 do
            ret['controller'] = ret['controller'] .. '/'..ucfirst(tmp[i])
        end
        ret['action'] = tmp[tmp_count]
    end
    local sub_tmp = string.sub(ret['module'], 1, #(MODULE_PREFIX))
    if(sub_tmp == MODULE_PREFIX)
        then
        ret['module'] = ucfirst(string.sub(ret['module'], #(MODULE_PREFIX) + 1))
    end
    return ret
end

return Application
