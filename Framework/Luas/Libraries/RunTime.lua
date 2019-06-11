--[[
**计时类
**使用方法:
local RunTime = require('RunTime')
RunTime:start('test1')
--body
RunTime:stop('test1')
local spent_info = RunTime:spent('test1')
@author huaqing1
--]]
local string = string
local RunTime = Class:new('RunTime')
local start_time = {}
local stop_time  = {}

local function calc_cost( key )
	local diff_time = (stop_time [key] - start_time [key]) * 1000
	return string.format("%.1f", diff_time)
end

function RunTime:start(key)
	if (empty(key)) then
		key = '_default_'
	end
	start_time [key] = microtime(1)
	return true
end

function RunTime:stop(key)
	if (empty(key)) then
		key = '_default_'
	end
	stop_time [key] = microtime(1)
	return true
end

function RunTime:spent(key) 
	if (empty(key)) then
		return self:getAllSpent()
	else
		return calc_cost(key)
	end
end

function RunTime:getAllSpent( )
	local result = {}
	if (not empty(stop_time)) then
		for key,v in pairs(stop_time) do
			result [key] = calc_cost(key)
		end
	end
	return result
end

function RunTime:clearTime( )
	start_time = {}
	stop_time  = {}
end


return RunTime