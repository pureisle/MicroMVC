local RedisTool = Class:new('RedisTool')
-- 构造方法
function RedisTool:new (resource_name, module)
    if empty(resource_name) or empty(module) then
        error('参数错误')
    end
    local Redis = require 'Redis'
    local red = Redis:new()
    local config = ConfigTool:loadByName(resource_name, module);
    if empty(config['host']) or empty(config['port']) then
        error('配置错误')
    end
    local timeout = config['timeout'] or 1
    red:set_timeout(timeout * 1000) -- sec
    local ok, err = red:connect(config['host'], config['port'])
    if not ok then
        error("failed to connect: " .. err)
        return
    end
    return red
end
return RedisTool
