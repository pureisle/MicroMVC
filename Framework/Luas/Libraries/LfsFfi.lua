-- 能安装大家常用的 luafilesystem最好，不方便安装使用本库当替代方案

local lfs = {
	_VERSION		= "LuaFileSystem 1.6.3 - FFI",
	_DESCRIPTION	= "LuaFileSystem-FFI is an implementation of LuaFileSystem functionality using LuaJIT FFI",
	_COPYRIGHT		= "LFS: Copyright (C) 2003-2015 Kepler Project LFSFFI: Copyright 2015 Pablo A. Mayobre",
	_URL			= "https://github.com/Positive07/lfs-ffi.lua"
}

local ffi = require "ffi"

local os = ffi.os == "Windows" and "windows" or "unix" --Simplify stuff (Probably shouldn't do so but fuck it for now)

local system = ({ --NOTE: You reading this should check the indexing in the closing bracket
	windows	= {
		--Needs to be tested in x64 too!!
		getcwd	= "_getcwd",
		chdir	= "_chdir",
		rmdir	= "_rmdir",
		mkdirn	= "_mkdir",

		mkdir	= "_mkdir (const char *path);",

		maxpath	= 260,

		timet	= "typedef __int64 time_t;",
		utimes	= "_utimebuf",
		utime	= "_utime",
	},
	unix	= {
		getcwd	= "getcwd",
		chdir	= "chdir",
		rmdir	= "rmdir",
		mkdirn	= "mkdir",

		mkdir	= "mkdir (const char *path, uint32_t mode);",

		maxpath	= 4096, --Good guy unix!

		timet	= "typedef long int time_t;", --Not sure if long int or long long int or __int32_t so fuck it!
		utimes	= "utimebuf",
		utime	= "utime",
	}
})[os]

ffi.cdef(
	[[
		char   *]] .. system.getcwd .. [[ ( char *buf, size_t size );
		int		]] .. system.chdir  .. [[ ( const char *path );
		int		]] .. system.rmdir  .. [[ (const char *pathname );
		int		]] .. system.mkdir  .. [[
				]] .. system.timet  .. [[
		struct	]] .. system.utimes .. [[ { time_t actime; time_t modtime; };
		int		]] .. system.utime  .. [[ ( unsigned char *file, struct ]] .. system.utimes .. [[ *times );
		]]
)

--(IMPLEMENTING STAT AND IT'S STRUCTURE IS WAY TOO HARD)
--lfs.attributes
--lfs.symlinkattributes

lfs.currentdir = function ()
	local buff = ffi.new("char[?]", system.maxpath)

	ffi.C[system.getcwd](buff, system.maxpath)

	return ffi.string(buff)
end

lfs.chdir = function (path)
	return ffi.C[system.chdir](path) == 0
end

if os == "windows" then
	ffi.cdef[[
		/* 32 bits only, should be reworked to support 64 bits */

		typedef struct _finddata32_t {
			uint32_t  attrib;
			uint32_t  time_create;    /* -1 for FAT file systems */
			uint32_t  time_access;    /* -1 for FAT file systems */
			uint32_t  time_write;
			uint32_t  size;
			char      name[260];
		} _finddata32_t;

		int _findfirst32	(const char* filespec, _finddata32_t*);
		int _findnext32		(int handle, struct _finddata32_t *fileinfo);
		int _findclose		(int handle);
	]]

	lfs.link = function () error("lfs.link is not supported in Windows systems", 1) end

	lfs.setmode = function () return true, "binary" end --This is wrong! should be int _setmode(int _fileno(FILE *stream), int mode);

	local iterator = function (dir)
		if dir.type ~= "directory" then error() end
		if dir.closed then error() end

		local cfile = ffi.new("struct _finddata32_t")

		if dir.hFile == 0 then --First entry
			dir.hFile = ffi.C._findfirst32(dir.pattern, cfile)

			if dir.hFile == -1 then
				return nil, 'Couldn\'t get entries for the directory: "'.. dir.path ..'"'
			else
				return ffi.string(cfile.name)
			end
		else
			dir.hFile = ffi.C._findnext32(dir.hFile, cfile)

			if dir.hFile == -1 then
				ffi.C._findclose(dir.hFile)
				dir.closed = true

				return nil
			else
				return ffi.string(cfile.name)
			end
		end
	end

	local close = function (dir)
		if dir.type ~= "directory" then error() end

		if not dir.closed and dir.hFile then
			ffi.C._findclose(dir.hFile)
			dir.closed = true
		end
	end

	local fold = function (path)
		return path
	end

	local dirmeta = {__index = {next = iterator, close = close}}

	lfs.dir = function (path)
		fold(path) --Windows path minimize (...8/8/8/8.3)

		if #path > system.maxpath - 1 then error('The path passed to lfs.dir is too long: "'..#path..'"\n PATH:'..#path..", MAXPATH:"..system.maxpath, 2) end

		local dir = setmetatable ({
			type	= "directory",
			path	= path,
			pattern	= path .. "/*",
			closed	= false,
			hFile	= 0,
		}, dirmeta)

		return iterator, dir, nil
	end
else
	ffi.cdef[[
		int link	(const char *oldname, const char *newname);
		int symlink	(const char *oldname, const char *newname);

		typedef struct	__dirstream DIR;

		typedef int32_t 	off_t;
		typedef uint32_t 	ino_t;
		typedef off_t		off64_t; /* Probably not usted but whatever */
		typedef ino_t		ino64_t; /* Probably not usted but whatever */

		struct dirent {
			ino_t			d_ino;
			off_t			d_off;
			unsigned short	d_reclen;
			unsigned char	d_type;
			char			d_name[256];
		};

		DIR			   *opendir		(const char *);
		struct dirent  *readdir		(DIR *);
		int				closedir	(DIR *);
	]]

	lfs.link = function (old, new, symlink)
		local f = symlink and ffi.C.symlink or ffi.C.link
		return f(old, new) == 0
	end

	lfs.setmode = function () return true, "binary" end

	local iterator = function (dir)
		if dir.type ~= "directory" then error() end
		if dir.closed then error() end

		local entry = ffi.C.readdir(dir.dir)

		if entry ~= nil then
			return ffi.string(entry.d_name)
		else
			ffi.C.closedir(dir.dir)
			dir.closed = true

			return
		end
	end

	local close = function (dir)
		if dir.type ~= "directory" then error() end

		if not dir.closed and dir.dir then
			ffi.C.closedir(dir.dir)
			dir.closed = true
		end
	end

	local dirmeta = {__index = {next = iterator, close = close}}

	lfs.dir = function (path)
		if #path > (system.maxpath - 1) then
			error('The path passed to lfs.dir is too long: "'..#path..'"\n PATH:'..#path..", MAXPATH:"..system.maxpath, 2)
		end

		local dir = ffi.gc(ffi.C.opendir(path), ffi.C.closedir) --Needs to be GC'ed
		if dir == nil then error('Couldn\'t open the directory: "'..path..'"', 2) end

		local dir = setmetatable ({
			type	= "directory",
			path	= path,
			dir		= dir,
			closed	= false,
		}, dirmeta)

		return iterator, dir, nil
	end
end

lfs.mkdir = function (path)
	local fail
	if os == "unix" then
		fail = ffi.C[system.mkdirn](path, 509) --octal for 0775 User:RWX, Group:RWX, Other:RX
	else
		fail = ffi.C[system.mkdirn](path)
	end

	if fail ~= 0 then
		return nil, 'Couldn\'t create the directory: "'..path..'"'
	else
		return true
	end
end

lfs.rmdir = function (path)
	local fail = ffi.C[system.rmdir](path)

	if fail ~= 0 then
		return nil, 'Couldn\'t remove the directory: "'..path..'"'
	else
		return true
	end
end

lfs.touch = function (path, actime, modtime)
	local buf

	if type(actime) == "number" then
		modtime	= modtime	or actime

		buf = ffi.new("struct "..system.utimes)
		buf.actime	= actime
		buf.modtime	= modtime
	end

	local p = ffi.new("unsigned char[?]", #path + 1) --FFI complains if we dont do this first!
	ffi.copy(p, path)

	if ffi.C[system.utime](p, buf) ~= 0 then
		return nil, 'Couldn\'t change the access and modification times of the file: "'..path..'"'
	else
		return true
	end
end

return lfs