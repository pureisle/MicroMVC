### Lua MVC框架
代码相关只针对 Openresty 进行了测试 , [简单的性能测试](https://github.com/pureisle/MicroMVC/blob/master/Framework/docs/lua_performance.md)。  
由于Lua代码相比较于PHP，开发效率确实有降低，所以 Lua MVC建议只做一些简单业务逻辑。如简单业务逻辑的下行接口等用Lua，复杂上行接口、后台等依然用PHP代码。
提供了适合PHP程序员使用的函数库 [GlobalFunction.lua](https://github.com/pureisle/MicroMVC/blob/master/Framework/Luas/GlobalFunction.lua) ，内含一些常用的PHP函数。  

### 相关文件
```
|- Framework	框架
	|- Luas		框架类库
		|- Application.lua 	主程序
```

### 使用方法
1. 配置Nginx入口:
```
...
http{
    ...
    lua_shared_dict micromvc_cache 64m;
    init_by_lua_file /data1/www/htdocs/MicroMVC/public/init.lua;
    ...
    server{
        ...

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
            #resolver 10.13.xx.xx 172.16.xxx.xxx  valid=600;
            default_type 'text/plain';
            lua_code_cache off; #生产环境注意修改为 on
            content_by_lua_file "${document_root}/index.lua";
            #content_by_lua 'ngx.say("hello, lua")';
        }
        location / {
	    if ( $uri ~ ^/*.lua$ ) {
                return 404;
            }
            set $script_uri "";
            if ( $request_uri ~* "([^?]*)?" ) {
                set $script_uri $1;
            }
            fastcgi_pass 127.0.0.1:9183;
            fastcgi_param SCRIPT_URL $script_uri;
            include fastcgi/comm_fastcgi_params;
        }
        ...
    }
    ...
}
```
另外最好在nginx.conf的配置里增加缓存配置：  lua_shared_dict micromvc_cache 64m;  
此项配置后，框架会缓存配置文件，各个nginx进程共享使用，能有效提升效率。  

有时候使用 Lua 的 reids 、mysql 用域名链接时（会报 no resolver defined to resolve  错误），  
需要额外在 location /index.lua 语句块内配置DNS解析服务器地址，如：  
resolver 10.13.xx.xx 172.16.xxx.xxx  valid=600;    
也可以直接配置 resolver 127.0.0.1 valid=600; 来使用本地DNS配置；  

类似PHP MVC里的运行环境配置，如果在nginx配置内设置： set $CURRENT_ENV_NAME dev;    未设置该值的话默认值为 pro  
则框架会搜索加载配置路径 dev 路径下的配置，否则加载默认配置路径下的配置文件。  

1. 此后URL访问类似PHP MVC框架，Module部分含有 "lua_" 字符串的，则统一重定向到 index.lua 入口文件。  
如：http://micromvc:8183/lua_sso/api/index/index?a=1&b=2
则会解析为：  
Module:  Sso  
Controller: Api/Index  
Action: index  
此时会搜寻到路径ROOT/Sso/Controllers/Api/Index.lua 并执行indexAction()代码
1. 框架类库 GlobalFunction.lua 提供了一些PHP中常用、好用的一些全局函数，以便PHP工程师方便的迁移到Lua上开发代码。
1. 类似 PHP 框架，Lua MVC 提供简单的模板解析功能，使用方法基本与PHP的使用方式类似。在Controller的Action内调用类似 self:assign({text = 'Hello,MicroMVC'}) 的方法即可。View 的搜索路径同PHP框架一致。lua [模板解析wiki文档见链接](https://github.com/bungle/lua-resty-template#template-syntax)
1. Tools.lua 提供了类似 PHP 的 require 和 require_once 函数，以避免lua自带的require自动替代"."符号等问题。
1. 关于Lua代码的面向对象编程约定：
* 类命名规范使用驼峰式命名
* 使用全局 Class:new('class_name',parent_obj=nil) 来构建类
* 使用 ClassName.methodName() 定义类静态方法
* 使用 ClassName:methodName() 定义类实例方法
* 使用 local FunctionName() 定义类私有方法
* 构建的业务类应定义为 local 变量，并在定义文件结尾 return 变量对象，以防项目中对象定义冲突
* 样例代码如下：
```
local SampleClass = Class:new('SampleClass')
SampleClass.param_a = 'A' -- 定义对象静态变量
SampleClass.PARAM_C = 'const' -- 定义对象常量
--定义构造函数
function SampleClass:new()
    local tmp = Class:new('SampleClassSon',self)
    tmp.param_b = 'B' --定义实例成员变量
    return tmp
end
--实例方法
function SampleClass:methodName()
    ngx.say('hello,MicroMVC')
    return true
end
--类静态方法
function SampleClass.methodName1()
end
--私有方法
local function _function()
end
return SampleClass
```
