--[[
-- MVC类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Application = {
    uri = '',
}
-- 构造方法
function Application:new (uri)
    o = {}
    setmetatable(o, {__index = self})
    self.uri = uri
    return o
end
function Application:run()
    local router_info = self:router()
    local require_path = router_info['module'] .. '/Controllers/'..router_info['controller'] .. ".lua"
    error_handler = function (msg)
        var_dump(msg)
    end
    require 'Controller'
    controller = self:autoLoad(require_path)
    xpcall(function ()
        c_name = controller.classCheck()--检验是否继承父类
    end, error_handler)
    xpcall(function ()
        c_ret = controller[router_info['action'] .. 'Action']()
    end, error_handler)
    var_dump(c_ret)
end
function Application:autoLoad(file)
    package.path = ROOT_PATH..'/'..file
    local ok, c_obj = pcall(require, file)
    if not ok then
        var_dump(c_obj)
        c_obj = function (narr, nrec) return {} end
    end
    return c_obj
end
function Application:router()
    local tmp = {}
    local i = 1
    for k in string.gmatch(self.uri, "([^?/]*)") do
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
