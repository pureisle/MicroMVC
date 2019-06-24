--[[
-- Controller基类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
Controller = Class:new('Controller')
-- 构造方法
function Controller:new ()
    local tmp = Class:new('Controller', self)
    tmp.render_params = {}
    return tmp
end
function Controller:classCheck()
    return self.class
end
function Controller:assign(params)
    self.render_params = params
    return self
end
function Controller:getAssign()
    return self.render_params
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
