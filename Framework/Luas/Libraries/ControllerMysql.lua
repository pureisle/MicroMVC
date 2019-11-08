--[[
类似 php 的controllermysql
-- 第一版 简单处理，sql拼接暂不支持
]]

local empty  = empty
local xpcall = xpcall
local error  = error

local mysql   = require('resty.mysql')
local ConfigTool = require 'ConfigTool';

local POOL_MAX_IDLE_TIME = 10000 --毫秒 
local POOL_SIZE = 100 --连接池大小 
local STATIC_DB = {}		--db 实例,静态变量,多个进程会共用改变量 ???

local ControllerMysql = {}

local mt = { __index = ControllerMysql }

local function createDbKey( module_name, resource_name )
	return module_name .. resource_name
end

local function close_db(db, pool_max_idle_time, pool_size)
    if not db then  
        return  
    end  
    --db:close()  
	--设置连接池，复用 
    local ok, err = db:set_keepalive(pool_max_idle_time or POOL_MAX_IDLE_TIME, pool_size or POOL_SIZE)
end  

local function connectDb(module_name, resource_name)
	local db_key = createDbKey(module_name, resource_name)
	if STATIC_DB[db_key] then
		--return STATIC_DB[db_key]	--不明白一用静态变量会出问题
	end
	--创建db实例  
	local db, err = mysql:new()
	if not db then
		error("new mysql error : " .. err)
	end
    local db_config = ConfigTool:loadByName(resource_name, module_name)
    -- set_timeout
    db:set_timeout(db_config.time_out)
	local res, err, errno, sqlstate = db:connect(db_config)  
	if not res then  
	   error("connect to mysql error : " .. resource_name .. ' ' .. err )  
	   return close_db(db)
	end
	STATIC_DB [db_key] = db
	return db
end


function ControllerMysql:new(table_name, module_name)
	if empty(table_name) or empty(module_name) then
		error('table_name is empty or module_name is empty')
	end
	
	return setmetatable({ table_name = table_name, module_name = module_name }, mt)
end

--[[
--对于新增/修改/删除会返回如下格式的响应：
{  
    insert_id = 0,  
    server_status = 2,  
    warning_count = 1,  
    affected_rows = 32,  
    message = nil  
} 
--对于查询会返回如下格式的响应：
{  
    { id= 1, ch= "hello"},  
    { id= 2, ch= "hello2"}  
} 
null将返回ngx.null。
]]
function ControllerMysql:exec( resource_name )
	if empty(resource_name) then
		error('resource_name is empty')
	end
	if empty(self.last_sql) then
		error('last_sql is empty')
	end
	local sql     = self.last_sql
	self.last_sql = nil
	local db = connectDb(self.module_name, resource_name)
	local res, err, errno, sqlstate =  db:query(sql)
	if not res then
	   error("mysql query error : " .. resource_name .. ' ' .. err )  
	end
	if res == ngx.null then
		res = nil
	end
	return res
end

function ControllerMysql:setSql( sql )
	self.last_sql = sql
	return self
end

function ControllerMysql:getLastSql( )
	return self.last_sql
end

function ControllerMysql:closeDb( resource_name )
	local module_name = self.module_name
	local db_key = createDbKey(module_name, resource_name)
	local db = STATIC_DB[db_key]
	if db then
    	local db_config = ConfigTool:loadByName(resource_name, module_name)
		close_db(db, db_config.pool_max_idle_time, db_config.pool_size)
		STATIC_DB [db_key] = nil  -- db链接关闭后，需要清空连接池
	end
end
-- 用于脚本结束时
function ControllerMysql:closeAllDb( )
	if empty(STATIC_DB) then
		return
	end
	for k,db in pairs(STATIC_DB) do
		STATIC_DB = {}
		close_db(db)
	end
end

function ControllerMysql:getDbList( ... )
	return STATIC_DB
end

return ControllerMysql
