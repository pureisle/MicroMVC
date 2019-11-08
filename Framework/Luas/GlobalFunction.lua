--[[
-- 全局公共函数 , 主要是为了方便PHP程序员做的一些迁移函数，尽可能保持名称和参数一致
--  @author zhiyuan <zhiyuan12@staff.weibo.com>
-- 
-- function list:
--  my_string (s)   转化为可以用下标的字符串 
--  ucfirst(s)      首字母大写
--  explode(symbol, s, limit)   拆分字符串为数组  
--  implode(glue, pieces)   按指定分隔符聚合数组 
--  strpos(s, pattern, offset)  查找目标字符位置 
--  trim(s, character_mask)     消除开始和结尾的空字符或指定字符
--  ltrim(s, character_mask)
--  rtrim(s, character_mask)   
--  var_dump(...)   变量输出   
--  empty(o)    与PHP empty() 同功能函数
--  file_exists(path)   判断路径是否为文件是否存在 
--  printf(format, ...)   按指定格式输出数据
--  date(format, time)  获取时间
--  microtime(get_as_float)   获取毫秒时间
--  json_encode(var)    json编码
--  json_decode(str)    json解码
--  sleep(sec)          非阻塞sleep
--  current_file()      获取当前文件名称，类似 php __FILE__
--  dirname(path, levels)   获取文件路径
--  file_get_contents(filename) 读取文件 
--  file_put_contents (filename, data) 写文件
--  in_array(needle, haystack, strict)  数组内搜索   ps : strict参数暂无支持
--  array_keys(array, search_value, strict)   获取数组的key   ps : strict参数暂无支持
--  array_unqiue(array, sort_flags)   给数组去重 ps:sort_flags没有实现
--  array_column(array, column_key,index_key)   获取多维数组里的column_key的值
--  array_sum(array)   一维数组求和
--  strtr(input, replace_pairs) 对input进行字符串替换
--  strtotime(date) 仅支持 Y-m-d H:i:s 或者 Y-m-d格式
--  min(value1,value2) or   min(table) 求最大值 注意只支持数字 --后续待完善
--  max(value1,value2) or   max(table) 求最小值 注意只支持数字
--  sha1(string)  计算string 的sha1
--]]
local ffi = require("FfiDefine")
local Json = require('cjson')
local string_gsub = string.gsub
local string_upper = string.upper
local string_sub = string.sub
local table_concat = table.concat
local table_insert = table.insert
local string_find = string.find
local string_format = string.format
local debug_getinfo = debug.getinfo
local table_remove = table.remove
local io_write = io.write
local io_open = io.open
local io_close = io.close
local os_date = os.date
local ngx_sleep = ngx.sleep
local ngx_say = ngx.say
local type = type
local assert = assert
local tonumber = tonumber
local pairs = pairs
local next = next
local tostring = tostring
local getmetatable = getmetatable
local newproxy = newproxy

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
    if type(s) ~= 'string' then
        return ''
    end
    return string_upper(s:sub(1, 1))..s:sub(2)
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
        ret[count] = string_sub(s, 1, pos - 1)
        s = string_sub(s, pos + symbol_len)
        pos = strpos(s, symbol)
    end
    return ret
end
-- 按指定分隔符聚合数组
function implode(glue, pieces)
    return table_concat(pieces, glue)
end
-- 无正则的匹配字符串
function strpos(s, pattern, offset)
    offset = offset or 1
    return string_find(s, pattern, offset, true)
end
-- 消除空字符
function trim(s, character_mask)
    local ret = rtrim(ltrim(s, character_mask), character_mask)
    return ret
end
function ltrim(s, character_mask)
    if(empty(character_mask)) then
        character_mask = '%s'
    end
    return string_gsub(s, "^"..character_mask.."+", "")
end
function rtrim(s, character_mask)
    if(empty(character_mask)) then
        character_mask = '%s'
    end
    return string_gsub(s, character_mask.."+$", "")
end
-- 变量输出
function var_dump(...)
    local _recurse
    _recurse = function (o, indent, deep)
        local indent2 = indent .. '  '
        if type(o) == 'table' then
            local s = '{' .. '\n'
            if deep > 10 then --防止递归层级过深
                return s .. indent2 .. '...\n' .. indent .. '}'
            end
            local first = true
            for k, v in pairs(o) do
                if first == false then s = s .. ', \n' end
                if type(k) ~= 'number' then k = '"'..tostring(k) .. '"' end
                s = s .. indent2 .. '[' .. k .. '] = ' .. _recurse(v, indent2, deep + 1)
                first = false
            end
            return s .. '\n' .. indent .. '}'
        elseif type(o) == 'string' then
            return '"'..tostring(o) .. '"'
        elseif type(o) == 'number' or type(o) == 'boolean' then
            return tostring(o)
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
        printf('%s', _recurse(args[1], '', 1))
    end
end
-- 模拟PHP的emtpy()
function empty(o)
    if type(o) == 'table' and next(o) == nil then
        return true
    elseif type(o) == 'nil' then
        return true
    elseif type(o) == 'boolean' then
        return not o
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
--判断文件是否存在 如果文件存在，返回true，不存在，返回false。
--[[这个函数还可以检查其它文件属性：
06     检查读写权限 
04     检查读权限 
02     检查写权限 
01     检查执行权限 
00     检查文件的存在性
]]
function file_exists(path, amode)
    amode = amode or 0
    return ffi.C.access(path, amode) == 0
    -- if empty(path) then
    --     return false
    -- end
    -- local file = io.open(path, "rb")
    -- if file then
    --     file:close()
    -- end
    -- return file ~= nil
end
-- 按指定格式输出数据
function printf(format, ...)
    local str = string_format(format, ...)
    if IS_CLI then
        if str ~= nil then
            io_write(str)
        end
    else
        ngx_say(str)
    end
end
-- 获取时间
function date(format, time)
    return os_date(format, time)
end
-- 获取毫秒时间
function microtime(get_as_float)
    local tm = ffi.new("struct timeval");
    ffi.C.gettimeofday(tm, nil);
    local sec = tonumber(tm.tv_sec);
    local usec = tonumber(tm.tv_usec);
    if get_as_float then
        return sec + usec * 10 ^- 6;
    else
        return usec.." "..sec
    end
end
-- json 编解码
function json_encode(var)
    return Json.encode(var)
end
function json_decode(str)
    return Json.decode(str)
end
-- 休眠指定时间
function sleep(sec)
    ngx_sleep(sec)
end
-- 获取当前文件路径
function current_file()
    return debug_getinfo(2, "S").source:sub(2)
end
-- 获取路径
function dirname(path, levels)
    local path_arr = explode('/', path)
    levels = levels or 1
    for i = 1, levels do
        table_remove(path_arr)
    end
    return table_concat(path_arr, '/')
end
-- 读取文件    ps: filename 后边的参数暂无支持
function file_get_contents(filename, use_include_path, context, offset, maxlen)
    local file = io_open (filename, 'r')
    if not file then
        return false
    end
    if not empty(offset) then
        file:seek('set', offset)
    end
    local tmp = file:read("*a")
    io_close(file)
    return tmp
end
-- 写文件   ps: data 后边的参数暂无支持
function file_put_contents(filename, data, flags, context)
    local file = io_open(filename, "w")
    local tmp = file:write(data)
    io_close(file)
    if tmp then
        return #data
    else
        return tmp
    end
end
-- 数组内搜索   ps : strict参数暂无支持
function in_array(needle, haystack, strict)
    if(empty(haystack) or type(haystack) ~= 'table') then
        return false;
    end
    for k, v in pairs(haystack) do
        if(needle == v)then
            return true
        end
    end
    return false
end
-- 获取数组的key   ps : strict参数暂无支持
function array_keys(haystack, search_value, strict)
    local ret = {}
    if(empty(haystack))then
        return ret
    end
    if(empty(search_value))then
        for k, v in pairs(haystack) do
            table_insert(ret, k)
        end
    else
        for k, v in pairs(haystack) do
            if(v == search_value)then
                table_insert(ret, k)
            end
        end
    end
    return ret
end
-- 对table里的元素去重
function array_unique(t,sort_flags) 
    local check = {}
    local new_table = {}
    if type(t) ~= 'table' or #t ==1 then 
        new_table = t
        return new_table
    end
    for k,v in pairs(t) do
        if not check[v] then
            table.insert(new_table, v)
            check[v] = 1
        end
    end
    return new_table
end
-- 获取多维数组里的column_key的值
function array_column(input, column_key, index_key) 
    local ret = {}
    if empty(input) then
        return ret
    end
    for k,v in pairs(input) do
        if type(v) == 'table' and rawget(v,column_key) ~= nil  then
            if index_key ~=nil then
                ret[rawget(v,index_key)] = rawget(v,column_key)
            else
            table.insert(ret, rawget(v,column_key))
            end
        end
    end
    return ret
end
-- 对数组里的值进行求和
function array_sum(t)
    local sum = 0
    if type(t) ~= 'table' then
        return sum
    end
    for k,v in pairs(t) do
        sum = sum+tonumber(v)
    end
    return sum
end
-- 用数组的传入数组，用key=》value的形式进行替换 不支持from to的替换方式
function strtr(input, replace_pairs) 
    local ret = input
    if type(input) ~= 'string' then
        return input
    end
    if type(replace_pairs) ~= 'table' or empty(replace_pairs) then
        return input
    end
    for k,v in pairs(replace_pairs) do
        if type(v) == 'table' then
            return input
        else
            ret = string.gsub(ret, k, v)
        end
    end
    return ret
end
--- 仅支持 Y-m-d H:i:s 或者 Y-m-d格式
function strtotime(date)
    local time_arr = explode(' ',date)
    local day_arr = explode('-', time_arr[1])
    local hour_arr = {}
    if time_arr[2] then
        hour_arr = explode(':', time_arr[2])
    end
    local hour= hour_arr[1] or 0
    local minute= hour_arr[2] or 0
    local second= hour_arr[3] or 0
    local time = os.time({
        day=day_arr[3],
        month=day_arr[2], 
        year=day_arr[1],
        hour= hour_arr[1] or 0,
        min= hour_arr[2] or 0,
        sec= hour_arr[3] or 0
        }
    )
    return time
end
-- 求最小值
function min(value, ...)
    local to_sort = {...}
    local min = 0
    if (#to_sort ==0 ) then
        if type(value) ~='table' then
            to_sort = {value}
        else 
            to_sort = value
        end
    else
        table.insert(to_sort, value)
    end
    min = table.remove(to_sort, 1)
    for k,v in pairs(to_sort) do
        repeat
            if type(v) == 'table' then
                break;
            end
            if v < min then
                min = v
            end
        until true

    end
    return min
end
-- 求最大值
function max(value, ...)
    local to_sort = {...}
    local max = 0
    if (#to_sort ==0 ) then
        if type(value) ~='table' then
            to_sort = {value}
        else 
            to_sort = value
        end
    else
        table.insert(to_sort, value)
    end
    max = table.remove(to_sort, 1)
    for k,v in pairs(to_sort) do
        repeat
            if type(v) == 'table' then
                break;
            end
            if v > max then
                max = v
            end
        until true

    end
    return max
end
function to_hex(str)
   return ({str:gsub(".", function(c) return string.format("%02X", c:byte(1)) end)})[1]
end
function sha1(str)
    return string.lower(to_hex(ngx.sha1_bin(str))) --将用sha1_bin得到的二进制转成16进制然后转成小写
end
