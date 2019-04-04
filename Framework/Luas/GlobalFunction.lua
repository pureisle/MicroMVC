--[[
-- 全局公共函数
-- 
-- function list:
--  my_string (s)   转化为可以用下标的字符串 
--  ucfirst(s)      首字母大写
--  explode(symbol, s, limit)   拆分字符串为数组  
--  implode(glue, pieces)   按指定分隔符聚合数组 
--  strpos(s, pattern, offset)  查找目标字符位置    
--  var_dump(...)   变量输出   
--  empty(o)    与PHP empty() 同功能函数
--  file_exists(path)   判断路径是否为文件是否存在   
--]]
-- 转化为可以用下标的字符串
function my_string (s)
    assert(type(s) == "string", "string expected")
    local ms = s or ""
    local u = newproxy(true)
    local mt = getmetatable(u)
    local relatpos = function(p)
        local l = #ms
        if p < 0 then p = l + p + 1 end
        if p < 1 then p = 1 end
        return p, l
    end
    mt.__index = function(_, k)
        assert(type(k) == "number", "number expected as key")
        local k, l = relatpos(k)
        if k <= l then
            return ms:sub(k, k)
        end
    end
    mt.__newindex = function(_, k, v)
        assert(type(k) == "number", "number expected as key")
        assert(type(v) == "string" and #v == 1, "character expected as value")
        local k, l = relatpos(k)
        if k <= l + 1 then
            ms = ms:sub(1, k - 1) .. v .. ms:sub(k + 1, l)
        end
    end
    mt.__len = function(_) return #ms end
    mt.__tostring = function(_) return ms end
    return u
end
-- 首字母大写
function ucfirst(s)
    local ts = my_string(s);
    ts[1] = string.upper(ts[1])
    return tostring(ts)
end
-- 按指定字符串分割成数组
function explode(symbol, s, limit)
    limit = limit or 9999
    local pos = strpos(s, symbol)
    local symbol_len = #(symbol)
    local ret = {}
    local count = 0
    while (pos ~= nil or #(s) > 0) do
        count = count + 1
        if(limit <= count or pos == nil)then
            ret[count] = s
            break
        end
        ret[count] = string.sub(s, 1, pos - 1)
        s = string.sub(s, pos + symbol_len)
        pos = strpos(s, symbol)
    end
    return ret
end
-- 按指定分隔符聚合数组
function implode(glue, pieces)
    local ret = ''
    for k, v in pairs(pieces) do
        ret = ret ..glue ..v
    end
    return string.sub(ret, #(glue) + 1)
end
-- 无正则的匹配字符串
function strpos(s, pattern, offset)
    offset = offset or 1
    return string.find(s, pattern, offset, true)
end
-- 变量输出
function var_dump(...)
    local string = function (o)
        return '"' .. tostring(o) .. '"'
    end
    local recurse
    recurse = function (o, indent)
        if indent == nil then indent = '' end
        local indent2 = indent .. '  '
        if type(o) == 'table' then
            local s = indent .. '{' .. '\n'
            local first = true
            for k, v in pairs(o) do
                if first == false then s = s .. ', \n' end
                if type(k) ~= 'number' then k = string(k) end
                s = s .. indent2 .. '[' .. k .. '] = ' .. recurse(v, indent2)
                first = false
            end
            return s .. '\n' .. indent .. '}'
        elseif type(o) == 'string' then
            return string(o)
        elseif type(o) == 'number' or type(o) == 'boolean' then
            return o
        elseif type(o) == 'function' or type(o) == 'thread' or type(o) == 'nil' or type(o) == 'userdata' then
            return type(o)
        else
            return type(o)
        end
    end
    local args = {...}
    if #args > 1 then
        for k, v in pairs(args) do
            var_dump(v)
        end
    else
        ngx.say(recurse(args[1]))
    end
end
-- 模拟PHP的emtpy()
function empty(o)
    if type(o) == 'table' and next(o) == nil then
        return true
    elseif type(o) == 'nil' then
        return true
    elseif type(o) == 'boolean' then
        return o
    elseif type(o) == 'number' and o == 0 then
        return true
    elseif type(o) == 'string' and (#(o) == 0 or o == '0' or o == 'false') then
        return true
    elseif type(o) == 'function' or type(o) == 'thread' or type(o) == 'userdata' then
        return false
    else
        return false
    end
end
-- 判断文件是否存在
function file_exists(path)
    if empty(path) then
        return false
    end
    local file = io.open(path, "rb")
    if file then
        file:close()
    end
    return file ~= nil
end
