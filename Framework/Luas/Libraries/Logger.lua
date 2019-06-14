--[[
-- 日志类

--]]

local os_date = os.date
local ngx_var = ngx.var
local string  = string
local math    = math
local ConfigTool = require 'ConfigTool';
local LogConfig  = require 'LogConfig'
local Logger = Class:new('Logger')

local LEVEL_EMERGENCY      = 'emergency';
local LEVEL_ALERT          = 'alert';
local LEVEL_CRITICAL       = 'critical';
local LEVEL_ERROR          = 'error';
local LEVEL_WARNING        = 'warning';
local LEVEL_NOTICE         = 'notice';
local LEVEL_INFO           = 'info';
local LEVEL_DEBUG          = 'debug';
local DEFAULT_BUSINESS     = 'default'; --业务日志默认名
local LOG_SEPARATOR        = "#_#";     --日志分隔符

local _LOG_FIELD 		   = {
	time      = true,
    server_id = true,
    host_name = true,
    uniqid    = true,
    level     = true,
    b_name    = true,
    log_text  = true
}

local _LOG_FIELD_KEY = {
	'time',
	'server_id',
	'host_name',
	'uniqid',
	'level',
	'b_name',
	'log_text'
}


local function uniqid()
	local time_now = microtime(1)
	math.randomseed(time_now)
	return time_now .. math.random(1,10000)
end

function Logger:new( config_name, module )
	local Logger_tmp = Class:new('Logger', self)
	Logger_tmp._buffer_cache       = {}
	Logger_tmp._UNIQUE_ID          = ''
	local conf = ConfigTool:loadByName(config_name, module)
	Logger_tmp._config = LogConfig:new(conf)
	if (empty(Logger_tmp._UNIQUE_ID) and _LOG_FIELD ['uniqid']) then
		Logger_tmp._UNIQUE_ID = uniqid()
	end
	return Logger_tmp
end

local function _write(Logger_obj, msg )
	var_dump(msg)
	local fp = Logger_obj._config:getHandle('a')
	return fp:write(msg)
end

local function _buildLogText(Logger_obj, params )
	local tmp = {}
	local switch = {
		["time"] = function ()
			local time_now = explode(' ', microtime())
			return os_date('%Y-%m-%d %H:%M:%S') .. '.' ..time_now [1] 
		end,
		['server_id'] = function (  )
			return ngx_var.server_addr
		end,
		['host_name'] = function (  )
			return ngx_var.hostname 
		end,
		['uniqid'] = function()
			return Logger_obj._UNIQUE_ID 
		end,
		['level']  = function ( msg )
			return msg 
		end,
		['b_name']  = function ( msg )
			return msg 
		end,
		['log_text']  = function ( msg )
			return msg 
		end,
	}

	for i, key in pairs (_LOG_FIELD_KEY) do
		local value = _LOG_FIELD [key]
		if ( value == true and switch [key]) then
			tmp [i] = switch [key](params [key])
		end
	end

	return implode(LOG_SEPARATOR,tmp) .. "\n"
end

function Logger:emergency(   message,   context ,  business_name  )
	self:log(LEVEL_EMERGENCY,  message,   context ,  business_name)
end

function Logger:alert(   message,   context ,  business_name  )
	self:log(LEVEL_ALERT,  message,   context ,  business_name)
end

function Logger:critical(   message,   context ,  business_name  )
	self:log(LEVEL_CRITICAL,  message,   context ,  business_name)
end

function Logger:error(   message,   context ,  business_name  )
	self:log(LEVEL_ERROR,  message,   context ,  business_name)
end

function Logger:warning(   message,   context ,  business_name  )
	self:log(LEVEL_WARNING,  message,   context ,  business_name)
end

function Logger:notice(   message,   context ,  business_name  )
	self:log(LEVEL_NOTICE,  message,   context ,  business_name)
end

function Logger:info(   message,   context ,  business_name  )
	self:log(LEVEL_INFO,  message,   context ,  business_name)
end

function Logger:debug(  message,  context , business_name  )
	self:log(LEVEL_DEBUG,  message,   context ,  business_name)
end

function Logger:log( level,  message,   context ,  business_name  )
	if(empty(message)) then
		return false
	end
	if (empty(business_name)) then
		business_name = DEFAULT_BUSINESS
	end
	local message    = self:interpolate(message, context);
    local params = {}
    params ['level'] 		= level
    params ['b_name']		= business_name
    params ['log_text']		= message --换行符转换
    local log_str = _buildLogText(self,params)
    return _write(self,log_str);
end

function Logger:interpolate( message, context )
	if(empty(context) or type(context) ~= 'table') then
		return message
	end
	if(empty(message)) then
		return message
	end
	for key,val in pairs(context) do
		if (type(val) == 'string') then
			local findstring = "{" .. key .. "}"
			local tmp, num = string.gsub(message, findstring, val, 1)
			if (not empty(tmp)) then
				message = tmp
			end
		end
	end
	return message
end

return Logger