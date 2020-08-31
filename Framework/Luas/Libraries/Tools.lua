--[[
-- 框架工具类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local type = type
local debug_getinfo = debug.getinfo
local dirname = dirname
local string_gsub = string.gsub
local os_time = os.time
Tools = Class:new('Tools')
Tools.ENV_DEV = 'dev' --开发环境
Tools.ENV_PRO = 'pro' --生产环境
Tools.ENV_INDEX_NAME = 'CURRENT_ENV_NAME'
Tools.env_name = ''
Tools._REQUIRE_CACHE = {}
-- 获取代码执行环境
function Tools:getEnv()
    --生产环境禁止变更设置
    if (FRAMEWORK.CURRENT_ENV_NAME == self.ENV_PRO) then
        return self.ENV_PRO
    end
    local env
    if empty(self.env_name) == false then
        env = self.env_name
    else
        env = FRAMEWORK.CURRENT_ENV_NAME
    end
    return env;
end
-- 设置代码执行环境
function Tools:setEnv(env)
    self.env_name = env;
end
-- 是否为cli方式运行
function Tools:isCli()
    if (type(FRAMEWORK.IS_CLI) == 'boolean') then
        return FRAMEWORK.IS_CLI
    end
    return false;
end
-- PHP的require方式加载
-- 可以按相对路径或绝对路径加载文件，需要指定文件后缀。
-- Tools:require('../../Framework/Luas/ConfigTool.lua')
function Tools:require(module_name)
    local first_char = module_name:sub(1, 1)
    local real_path
    if first_char == '/' then
        --绝对路径处理
        real_path = module_name
    else
        --相对路径转化为绝对路径
        local deep = 2
        local tmp_name = debug_getinfo(2, "n")['name']
        if tmp_name == 'require_once' then
            deep = 3
        end
        local call_path = dirname(debug_getinfo(deep, "S").source:sub(2))
        real_path = call_path..'/'..module_name
    end
    if not file_exists(real_path) then
        error('require file not exist. path: '..real_path)
    end
    return dofile(real_path)

end
-- PHP的require_once方式加载
function Tools:require_once(module_name)
    if empty(self._REQUIRE_CACHE[module_name]) then
        self._REQUIRE_CACHE[module_name] = self:require(module_name)
    end
    return self._REQUIRE_CACHE[module_name]
end
return Tools
