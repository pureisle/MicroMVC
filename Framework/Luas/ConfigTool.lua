--[[
-- 配置读取类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local string_sub = string.sub
local table_remove = table.remove
local ngx_cache = ngx.shared[FRAMEWORK.NGX_CACHE_KEY]
local ini = require 'IniParser'
local DIRECTORY_SEPARATOR = FRAMEWORK.DIRECTORY_SEPARATOR
local ROOT_PATH = FRAMEWORK.ROOT_PATH
local CONFIG_FOLDER = FRAMEWORK.PATH.config
local ConfigTool = Class:new('ConfigTool')
ConfigTool.FILE_SUFFIX = '.lua'
ConfigTool.INI_SUFFIX = '.ini'
_ConfigTool_CACHE = {}
function ConfigTool:getConfig(file_name, module_name, file_suffix)
    if empty(file_name) then
        return false
    end
    file_suffix = file_suffix or self.FILE_SUFFIX
    local file_path = self:getFilePath(file_name, module_name, file_suffix);
    local cache_key = 'ConfigTool:'..file_path
    if not empty(ngx_cache:get(cache_key)) then
        return json_decode(ngx_cache:get(cache_key), true)
    end
    if not empty(_ConfigTool_CACHE[file_path]) then
        return _ConfigTool_CACHE[file_path]
    end
    if not file_exists(file_path) then
        return false
    end
    local config
    if file_suffix == self.FILE_SUFFIX then
        config = require(string_sub(file_path, 1, -#(self.FILE_SUFFIX) - 1))
    elseif file_suffix == self.INI_SUFFIX then
        config = ini:decodeByFile(file_path, true)
    else
        config = file_get_contents(file_path)
    end
    ngx_cache:set(cache_key, json_encode(config))
    _ConfigTool_CACHE[file_path] = config
    return config
end
function ConfigTool:getFilePath(file_name, module_name, file_suffix)
    file_suffix = file_suffix or self.FILE_SUFFIX
    local path = ROOT_PATH .. DIRECTORY_SEPARATOR .. module_name .. DIRECTORY_SEPARATOR .. CONFIG_FOLDER .. DIRECTORY_SEPARATOR .. file_name .. file_suffix
    local env = Tools:getEnv();
    if Tools.ENV_PRO ~= env then
        local test_path = ROOT_PATH .. DIRECTORY_SEPARATOR .. module_name .. DIRECTORY_SEPARATOR .. CONFIG_FOLDER .. DIRECTORY_SEPARATOR .. env .. DIRECTORY_SEPARATOR .. file_name .. file_suffix
        if file_exists(test_path) then
            path = test_path
        end
    end
    return path
end
--  根据配置名加载配置
--
--    如：log.file_name:firehose,将获取 module_name 下config内的log文件夹内的配置文件file_name内的firehose配置项
--
--    @param  string  config_name 字符串解析规则：配置文件名_配置名
function ConfigTool:loadByName(config_name, module_name, file_suffix)
    if empty(config_name) then
        return false
    end
    local tmp = explode('.', config_name)
    local file_name = table_remove(tmp)
    local resource_name
    if strpos(file_name, ':') then
        local tmp_file = explode(':', file_name)
        file_name = tmp_file[1]
        resource_name = tmp_file[2]
    end
    local file_path
    if not empty(tmp) then
        file_path = implode(DIRECTORY_SEPARATOR, tmp) .. DIRECTORY_SEPARATOR .. file_name;
    else
        file_path = file_name;
    end
    local config_array = self:getConfig(file_path, module_name, file_suffix)
    if (empty(resource_name) or empty(config_array[resource_name])) then
        return config_array
    end
    return config_array[resource_name]
end

return ConfigTool
