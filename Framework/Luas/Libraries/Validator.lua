--[[
/**
 * 数据合法性验证
 *
 * $rule_set = array(
 * 'a' => 'requirement',
 * 'b' => 'number&max:15&min:10',
 * 'c' => 'timestamp',
 * 'd' => 'enum:a,1,3,5,b,12345'
 * );
 * $ret = Validator:check($params, $rule_set);
 * Validator:getErrorMsg();
 *
 * @author zhiyuan <zhiyuan12@staff.weibo.com>
 */
]]

local string_sub = string.sub
local empty      = empty
local string_find = string.find
local math_ceil  = math.ceil
local tonumber   = tonumber
local string_len = string.len
local string_format = string.format

Validator = {}

local KEY_WORD = { -- 后续可以补充
	requirement   = 'The "%s" must be existed', 		-- 没有参数索引或空字符串无法通过检测
	boolean       = 'The "%s" must be a boolean',  		-- 参数值为严格的true或false通过检测
    integer       = 'The "%s" must be an integer', 	-- 整型数通过检测,包括字符串数字
	number        = 'The "%s" must be an number', 		-- 合法数学计数值或相应字符串通过检测,包括字符串数字
	table         = 'The "%s" must be an table',
	string        = 'The "%s" must be string',		    -- 字符串，包括数字
	date          = 'The "%s" must be a date and format like: Y-m-d H:i:s', --Y-m-d H:i:s 时间格式通过检测

    max           = 'The "%s" must less then %s',               --不大于指定的ASCII编码串通过检测
    min           = 'The "%s" must more then %s',               --不小于指定的ASCII编码串通过检测
    not_in        = 'The "%s" must not in %s',               --不在指定的ASCII编码串通过检测
    length_max    = 'The "%s" string length must less then %s', --字符串长度不大于指定个数的通过检测
    length_min    = 'The "%s" string length must more then %s', --字符串长度不小于指定个数的通过检测
    enum          = 'The "%s" must in %s',                      --符合枚举的字符串通过检测 enum:1,2,3,a,b
    not_empty     = 'The "%s" can not be empty',                --参数不能为空,'',0,false等均为空

    default      = ''  --参数没有传递时，设置默认值。 如果参数传递了即便无值，也不会设置默认值，因为确实会有传递空字符串的情况。
}

local function isRequirement( rule_string )
	if string_find(rule_string, 'requirement') then
		return true
	else
		return false
	end
end
local function isDefault( rule_string )
	if string_find(rule_string, 'default:') then
		return true
	else
		return false
	end
end

local function _runRuleCheck( rule, valid, data )
	local switch = {
		requirement = function ( data )
			return data ~= nil
		end,
		boolean 	= function ( data )
			return type(data) == 'boolean'
		end,
		number      = function ( data )
			return type(tonumber(data)) == 'number'
		end,
		integer     = function ( data )
			local data_number = tonumber(data)
			if type(data_number) == 'number' then
				return math_ceil(data_number) == data_number
			end
			return false
		end,
		string      = function ( data )
			if data then
				return type( data .. '') == 'string'  -- ..是为了数字类也可以认为是字符串
			end
			return false
		end,
		table       = function ( data )
			return type(data) == 'table'
		end,
		date        = function ( data )
			local pattern = "%d%d%d%d%-%d%d%-%d%d %d%d:%d%d:%d%d"
			local find_index = string_find(data, pattern)
			if find_index then
				return (string_sub(data, find_index)) == data
			else
				return false
			end
		end,
		max         = function ( data, valid )
			return tonumber(data) < tonumber(valid)
		end,
		min 		= function ( data, valid )
			return tonumber(data) > tonumber(valid)
		end,
		not_in 		= function ( data, valid )
			local arr = explode(',', valid) or {}
			return not in_array(data, arr)
		end,
		length_equal 		= function ( data, valid )
			return string_len(data) == tonumber(valid)
		end,
		length_max 		= function ( data, valid )
			return string_len(data) < tonumber(valid)
		end,
		length_min 		= function ( data, valid )
			return string_len(data) > tonumber(valid)
		end,
		enum            = function ( data, valid )
			return string_find(',' .. valid .. ',', ',' .. data .. ',') ~= nil
		end,
		not_empty       = function ( data )
			return not empty(data)
		end
	}
	if switch [rule] then
		return switch[rule](data, valid)
	else
		return true	--没有设定规则的，则默认true，即不走校验
	end
end

--[[
return true,nil |false,error_msg
]]
local function _parseRule(rule_string, data, key) 
	local len = string_len(rule_string)
    local begin = 1
    for i=1,len do
    	if ('&' == string_sub(rule_string, i, i) or i == len) then
			if i == len then
				i = i + 1
			end
			local tmp = string_sub(rule_string, begin, i - 1)
			local rule_tmp = explode(':', tmp)
			rule = rule_tmp [1]
			local valid = rule_tmp [2]  or nil
			local check_ret  = _runRuleCheck(rule, valid, data)
			if check_ret == false then
				if type(data) ~= 'string'  then
					data = json_encode(data)
				end
				--var_dump(rule,key,valid,data)
            	local error_msg = string_format(KEY_WORD[rule], key, valid) .. ' but value is ' .. data;
				return false,error_msg
			end
			begin = i + 1
		end
    end
    return true,nil
end
--[[
	return true,data | false, errormsg
]]
function Validator:check( data, rule_set )
	rule_set = rule_set or {}
	data     = data or {}
	if empty(rule_set) or empty(data) then
		return true, data
	end
	local ok = true
	local error_msg = ''
	local ret_data = {}
	for key,value in pairs(rule_set) do
		if data [key] then
			ok, error_msg = _parseRule(value, data [key], key)
			if not ok then
				return false,error_msg
			end
			ret_data [key] = data [key]
		else
			if isDefault(value) then
				local tmp = explode('default:', value)
				tmp = explode('&', tmp [2] or '')
				ret_data [key] = tmp[1] or ''
			else
				if isRequirement(value) then
					ok = false
					error_msg = string_format(KEY_WORD['requirement'], key)
					return false,error_msg
				end
			end
		end
	end
	return ok, ret_data
end

return Validator
