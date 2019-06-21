--[[
-- MVC类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local ngx_exit = ngx.exit
local string_sub = string.sub
local xpcall = xpcall
local empty = empty
local ucfirst = ucfirst
local Application = Class:new('Application')
Application.FRAMEWORK_VERSION = '1.1.4'
-- 构造方法
function Application:new (uri)
    self.uri = uri
    return Class:new('Application', self)
end
function Application:run()
    local router_info = self:router()
    local require_path = router_info['module'] .. '/Controllers/'..router_info['controller']
    local controller
    local http_code = ngx.HTTP_OK
    xpcall(function ()
        controller = require(require_path)
        local c_name = controller:classCheck()--检验是否继承父类
    end, function (msg)
        http_code = ngx.HTTP_NOT_FOUND
    end)
    if http_code == ngx.HTTP_OK then
        xpcall(function ()
            local c_ret = controller[router_info['action'] .. 'Action'](controller)
        end, function (msg)
            if(empty(rawget(controller, router_info['action'] .. 'Action'))) then
                http_code = ngx.HTTP_NOT_FOUND
            else
                http_code = ngx.HTTP_INTERNAL_SERVER_ERROR
                ngx.log(ngx.ERR, msg, "\n", debug.traceback())
            end
        end)
    end
    ngx_exit(http_code)
end
function Application:router()
    local tmp = {}
    local i = 1
    local uri = self.uri
    local q_mark, to, err = ngx.re.find(uri, "[?]", "jo")
    if(q_mark ~= nil)then
        uri = string_sub(uri, 1, q_mark - 1)
    end
    local it = ngx.re.gmatch(uri, "([^?#/]+)", "jo")
    while true do
        local m, err = it()
        if err then
            ngx.log(ngx.ERR, "error: ", err)
            return
        end
        if not m then
            break
        end
        tmp[i] = m[1]
        i = i + 1
    end
    local tmp_count = #(tmp)
    local ret = {['module'] = 'Index', ['controller'] = 'Index', ['action'] = 'index'}
    local f_m_p = FRAMEWORK.MODULE_PREFIX
    if(tmp_count == 1)
        then
        if tmp[1] ~= f_m_p then
            ret['module'] = tmp[1]
        end
    elseif(tmp_count == 2)
        then
        ret['module'] = tmp[1]
        ret['controller'] = ucfirst(tmp[2])
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

    local sub_tmp = string_sub(ret['module'], 1, #(f_m_p))
    if(sub_tmp == f_m_p)
        then
        ret['module'] = string_sub(ret['module'], #(f_m_p) + 1)
    end
    ret['module'] = ucfirst(ret['module'])
    return ret
end

return Application
