--[[
-- log 配置类，实现获取句柄
--]]

local error = error
local os_execute = os.execute
local os_date = os.date
local io = io
local LogConfig = Class:new('LogConfig')


function LogConfig:new( config )
	if(type(config) ~= 'table') then
		error('logconfig not table')
	end
	if(empty(config['root_path'])) then
		error('config.root_path empty')
	end
	local logconfig_tmp = Class:new('LogConfig', self)
	logconfig_tmp._root_path = config['root_path']
	logconfig_tmp._file_name 			= ''
	logconfig_tmp._buffer_line_num      = 20	--内存缓冲行数。进程异常退出会丢失数据
	logconfig_tmp._suffix_date_format 	= '%Y%m%d' --格式，跟php不一致
	logconfig_tmp._is_use_buffer	    = false 		--是否启用缓冲模式,暂时写死false，后续优化支持buffer
	logconfig_tmp._new_date             = ''
	logconfig_tmp._last_date            = ''
	logconfig_tmp._last_fp              = ''
	logconfig_tmp._last_file            = ''
	--判断目录是不是存在
	if(not file_exists(logconfig_tmp._root_path)) then
		os_execute("mkdir -p " .. logconfig_tmp._root_path)
	end
	if(empty(config['file_name'])) then
		error('config.file_name empty')
	end
	logconfig_tmp._file_name = config['file_name']
	if(config['buffer_line_num']) then
		logconfig_tmp._buffer_line_num = config['buffer_line_num']
	end
	if(config['suffix_date_format']) then
		logconfig_tmp._suffix_date_format = config['suffix_date_format']
	end
	if (type(config['is_use_buffer']) == 'boolean') then	--后续在支持
		--self._is_use_buffer = config.is_use_buffer
	end
	return logconfig_tmp
end

function LogConfig:getLogFileName(  )
	self._new_date = os_date(self._suffix_date_format)
	return self._file_name .. self._new_date .. '.log'
end

function LogConfig:getFilePath(  )
	return self._root_path .. '/' .. self:getLogFileName()
end

function LogConfig:getHandle( mode )
    local file_name = self:getLogFileName()
    if( self._last_date ~= self._new_date) then
    	local file_path = self:getFilePath()
    	self:closeHandle()
    	local fp = assert(io.open(file_path, mode))
    	self._last_fp = fp
    	self._last_date = self._new_date
    	self._last_file = file_path
    end
    return self._last_fp
end

function LogConfig:closeHandle(  )
	self._last_date = ''
	if (not empty(self._last_fp)) then
		self._last_fp:close()
	end
end

return LogConfig