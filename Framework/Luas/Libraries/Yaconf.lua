--[[
Yaconf的配置文件读取逻辑和计算逻辑的Lua类
目前仅支持.分隔的方式的ini文件,暂不支持存在[]section方式
--]]
Yaconf = {}
local ini = require 'IniParser'
local separator = '.'
local ngx_cache = ngx.shared[FRAMEWORK.NGX_CACHE_KEY]
local function _keyArr(keys, value)
	local table_keys = {}
	if next(keys) == nil then
		return value
	end

	local tmp_key = table.remove(keys)
	table_keys[tmp_key] = value
	while(true) do
		if next(keys) == nil then
			break
		end
		tmp_key = table.remove(keys)
		local tmp_table_keys = table_keys
		table_keys = {}
		table_keys[tmp_key] = tmp_table_keys
		-- 释放临时变量
		tmp_table_keys = nil
		
	end
	return table_keys
end
--[[
	Lua的对象和function都是用的引用传递 函数里变更了。外面对应的变量也会变更
--]]
local function _recurseTable(key_table, value, config)
	local tmp_key = table.remove(key_table,1)
	if config[tmp_key] then
		_recurseTable(key_table, value, config[tmp_key])
	else	
		local tmp_v = _keyArr(key_table, value)
		config[tmp_key] = tmp_v
	end
end

function Yaconf:get(yaconf_name)

	local  cache_key = 'Yaconf:'..yaconf_name
	if not empty(ngx_cache:get(cache_key)) then
		-- return json_decode(ngx_cache:get(cache_key))
	end
	local yaconf_dir = ''
	local directory_cache_key = 'Yaconf:directory'
	if not empty(ngx_cache:get(directory_cache_key)) then
		yaconf_dir = ngx_cache:get(directory_cache_key)
	else
		local fp = io.popen("php -i|grep yaconf.directory");
		local yaconf_dir_str = fp:read("*all");
		local yaconf_dir_arr = explode("=>",yaconf_dir_str);
		local yaconf_dir = trim(yaconf_dir_arr[3]);
		ngx_cache:set(directory_cache_key,yaconf_dir)
	end
	--  要写到缓存里，缓存里没有再去执行系统命令
	local yaconf_file = yaconf_dir.. "/"..yaconf_name..".ini"
	conf = ini:decodeByFile(yaconf_file)
	local config = {}
	for key, value in pairs(conf) do
		value = trim(value,"'")
		local tmp_keys = explode(separator, key)
		 _recurseTable(tmp_keys, value, config)
	end
	local ret = ngx_cache:set(cache_key, json_encode(config), 60000) --过期时间是毫秒单位
	return config
end
return Yaconf
