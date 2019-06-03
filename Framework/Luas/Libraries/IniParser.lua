--[[
-- ini配置类
-- 为了与PHP代码保持一致,process_sections 参数为true时，会进行 section 模式编码和解码
-- 
-- @author zhiyuan <zhiyuan12@staff.weibo.com>
--]]
local IniParser = Class:new('IniParser')
function IniParser:decodeByFile(file_path, process_sections)
    local str = file_get_contents(file_path)
    return self:decode(str, process_sections)
end
function IniParser:decode(ini_str, process_sections)
    local tmp_str = explode("\n", ini_str)
    local section_name = ''
    local data = {}
    for i = 1, #tmp_str do
        local tmp = explode("=", tmp_str[i], 2)
        if #tmp == 1 and process_sections == true then
            section_name = tmp[1]:match('^%[([^%[%]]+)%]$')
            data[section_name] = {}
        elseif #tmp == 2 then
            local key = trim(tmp[1])
            local tkey = key:match('%[([^%[%]]+)%]$')
            local tname
            if tkey then
                tname = key:match('^([^%[%]]+)')
                if not empty(section_name) then
                    if empty(data[section_name][tname]) then
                        data[section_name][tname] = {}
                    end
                    data[section_name][tname][tkey] = trim(trim(tmp[2]), '"')
                else
                    if empty(data[tname]) then
                        data[tname] = {}
                    end
                    data[tname][tkey] = trim(trim(tmp[2]), '"')
                end
            else
                if not empty(section_name) then
                    data[section_name][key] = trim(trim(tmp[2]), '"')
                else
                    data[key] = trim(trim(tmp[2]), '"')
                end
            end
        end
    end
    return data
end
function IniParser:encode(data, process_sections)
    if type(data) ~= 'table' then
        return false
    end
    local str = ''
    for section_name, section_info in pairs(data) do
        if type(section_info) == 'table' then
            if process_sections == true then
                str = str.. ("[%s]\n"):format(section_name)
            end
            for k, v in pairs(section_info) do
                if type(v) == 'table' then
                    for tk, tv in pairs(v) do
                        str = str.. ("%s[%s] = %s\n"):format(k, tk, self:_strtrans(tv))
                    end
                else
                    str = str.. ("%s = %s\n"):format(k, self:_strtrans(v))
                end
            end
        else
            str = str.. ("%s = %s\n"):format(section_name, self:_strtrans(section_info))
        end
    end
    return str
end
function IniParser:_strtrans(s)
    if (type(s) == 'string') then
        return ('"%s"'):format(s)
    else
        return s;
    end
end
return IniParser
