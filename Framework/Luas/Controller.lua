--[[
-- Controller基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Controller = Class:new('Controller')
-- 构造方法
function Controller:new ()
    return Class:new('Controller', self)
end
function Controller:classCheck()
    return 'Controller'
end
function Controller:getGetParams()
    return ngx.req.get_uri_args()
end
function Controller:getPostParams()
    ngx.req.read_body()
    return ngx.req.get_post_args()
end
function Controller:getJsonParams()
end
return Controller
