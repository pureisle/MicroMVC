--[[
-- 配置读取类
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
ConfigTool = Class:new('ConfigTool')
ConfigTool.FILE_SUFFIX = '.lua'
-- 构造方法
function ConfigTool:new ()
    return self
end
function ConfigTool:getConfig(file_name, module_name)
    if empty(file_name) then
        return false
    end
    local file_path = self:getFilePath(file_name, module_name);
    -- need to do
    -- config cache
    -- if ( ! isset(self::$_CONFIG_SET[$file_path])) {
    --     if ( ! file_exists($file_path)) {
    --         //配置文件不存在
    --         return false;
    --     } else {
    --         self::$_CONFIG_SET[$file_path] = include $file_path;
    --     }
    -- }
    return dofile(file_path)
end
function ConfigTool:getFilePath(file_name, module_name)
    local path = ROOT_PATH .. DIRECTORY_SEPARATOR .. module_name .. DIRECTORY_SEPARATOR .. CONFIG_FOLDER .. DIRECTORY_SEPARATOR .. file_name .. self.FILE_SUFFIX;
    local env = Tools:getEnv();
    if Tools.ENV_PRO ~= env then
        local test_path = ROOT_PATH .. DIRECTORY_SEPARATOR .. module_name .. DIRECTORY_SEPARATOR .. CONFIG_FOLDER .. DIRECTORY_SEPARATOR .. env .. DIRECTORY_SEPARATOR .. file_name .. self.FILE_SUFFIX
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
function ConfigTool:loadByName(config_name, module_name)
    if empty(config_name) then
        return {}
    end
    local tmp = explode('.', config_name)
    local file_name = table.remove(tmp)
    if strpos(file_name, ':') then
        local tmp_file = explode(':', file_name)
        file_name = tmp_file[1]
        resource_name = tmp_file[2]
    end
    local file_path
    if not empty(tmp) then
        file_path = implode(DIRECTORY_SEPARATOR, tmp) .. DIRECTORY_SEPARATOR . file_name;
    else
        file_path = file_name;
    end
    local config_array = self:getConfig(file_path, module_name)
    if (empty(resource_name) or empty(config_array[resource_name])) then
        return config_array
    end
    return config_array[resource_name]
end

return ConfigTool
