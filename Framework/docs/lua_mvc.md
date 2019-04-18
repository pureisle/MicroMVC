### Lua MVC框架
代码相关只针对 Openresty 进行了测试 。  
由于Lua代码相比较于PHP，开发效率确实太低，所以 Lua MVC建议只做一些简单业务逻辑。
提供了适合PHP程序员使用的函数库 [GlobalFunction.lua](https://github.com/pureisle/MicroMVC/blob/master/Framework/Luas/GlobalFunction.lua) ，内含一些常用的PHP函数  
如从 Redis 获取指定 Key 并返回内容或用Nginx + Lua 构建生产机上的连接池，以便提升PHP利用资源的效率等。
### 相关文件
```
|- Framework	框架
	|- Luas		框架类库
		|- Application.lua 	主程序
```

### 使用方法
1. 配置Nginx入口:
```
    root /data1/www/htdocs/MicroMVC/public/;
    set $flag 0;
    if ( $uri ~ ^/lua_.* ) {
        set $flag "${flag}1";
    }
    if ( !-f $request_filename ) {
        set $flag "${flag}2";
    }
    if ( $flag = "012" ) {
        rewrite "^/(.*)" /index.lua last;
    }
    if ( $flag = "02" ) {
        rewrite "^/(.*)" /index.php/$1 last;
    }

    location ~* \.(?:ico|css|js|gif|jpe?g|png)$ {
        expires 30d;
        add_header Vary Accept-Encoding;
        access_log off;
    }
    location /index.lua {
        default_type 'text/plain';
        lua_code_cache off;
        content_by_lua_file "${document_root}/index.lua";
        #content_by_lua 'ngx.say("hello, lua")';
    }
    location / {
        set $script_uri "";
        if ( $request_uri ~* "([^?]*)?" ) {
            set $script_uri $1;
        }
        fastcgi_pass 127.0.0.1:9183;
        fastcgi_param SCRIPT_URL $script_uri;
        include fastcgi/comm_fastcgi_params;
    }
```
1. 此后URL访问类似PHP MVC框架，Module部分含有 "lua_" 字符串的，则统一重定向到 index.lua 入口文件。  
如：http://micromvc:8183/lua_sso/api/index/index?a=1&b=2
则会解析为：  
Module:  Sso  
Controller: Api/Index  
Action: index  
此时会搜寻到路径ROOT/Sso/Controllers/Api/Index.lua 并执行indexAction()代码
1. 框架类库 GlobalFunction.lua 提供了一些PHP中常用、好用的一些全局函数，以便PHP工程师方便的迁移到Lua上开发代码。
1. 关于Lua代码的面向对象编程约定：
* 使用全局Class:new('class_name',parent_obj=nil) 来构建类
* 构建的业务类应定义为 local 变量，并在定义文件结尾 return 变量对象，以防项目中对象定义冲突
* 样例代码如下：
```
local Sso_Controller = Controller:new()
local sm = require 'Sso/Models/Sample'
local json = require 'Json'
function Sso_Controller:indexAction()
    -- var_dump(json.encode({1, 2, 3, {x = 10}}))
    sm:new()
    return true
end
return Sso_Controller
```