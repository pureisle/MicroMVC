### 为什么用
* 不过度设计，简单、清晰、好用；
* 提供最简单基础的 MVC 框架，将性能损耗降到最低；
* 按 Module 进行资源分离，以便对业务进行微服务化隔离或后期的服务便捷迁移；
* 提供简单好用的单元测试框架；
* 提供便捷的接口参数合法性验证服务，接口合法性调用检测服务，做了基础的防SQL注入、CSRF、XSS等安全防护；
* 提供便捷的异常处理服务；
* 提供简单好用工具类，如 Mysql 、Curl 等资源的封装、Xhprof 性能优化工具等；
* 根据代码运行的环境自动加载相应的配置文件，方便的切换仿真、生产环境；
* 提供PSR-3规范的日志类，额外提供 log buffer 功能（性能提升） 和 全局日志标记码（一个进程一个标记码，方便定位问题）的功能；
* 所有开发基于 PHP7 环境，未做低版本运行验证和兼容；

### 文件目录
```
|- Framework	框架
	|- config	框架全局配置
	|- Libraries	框架类库
	|- Entities	数据实体类
	|- Models	框架逻辑
	|- Tests	单元测试
|- public  框架公开访问位置
	|- index.php 	入口文件
	|- cli.php 	命令行入口
	|- run_test.php 	单元测试入口
	|- run_daemon.php   后台任务执行入口
|- Sso	样例应用,一个简单的Sso用户单点登录系统
	|- Cache	缓存管理文件
	|- config	应用配置文件（mysql、redis等资源配置；api接口配置等;不能变动）
	|- Controllers	控制器（不能变动）
	|- Daemons  后台进程任务类（不能变动）
	|- Models	业务逻辑类 
	|- Data 	数据访问类
	|- Entities 业务实体类
	|- Views	视图文件（不能变动）
	|- Plugins	插件文件
	|- Libraries	类库文件
	|- Tests	单元测试文件（不能变动）
	|- Bootstrap.php 	应用启动初始化文件（不能变动,可以没有）
```
文件结构也体现了 "DMVC" 的分层思想。  
* "D" 层为数据层，对应的文件夹为"Data"。该层主要解决数据结构的封装，对上层屏蔽底层的数据结构、存储工具等细节；  
* "M" 层为逻辑层，对应的文件夹为"Models"。该层主要解决业务逻辑的封装，对上层屏蔽业务逻辑的细节;  
* "C" 层为控制层，对应的文件夹为"Controllers"。该层主要控制相应URL提供哪些服务，根据自身提供的服务引用Model层提供的服务。该层主要负责：安全检验、登录检验、根据接口的目的调用相应 Model、日志记录等;  
* "V" 层为视图层，对应文件夹为"Views"。该层主要提供前端页面渲染的代码，主要为HTML代码，夹杂少量JS、CSS等，或作为目前流行的前后端分离设计的前端入口文件等;  
* "public" 文件夹主要存放静态资源，如图片、JS代码库、CSS表等；  

详细的情况可以参见 Sso Module，有更多的使用样例，包括 Cache、config、Bootstrap.php等

### 开始使用

#### MVC框架
1. 配置 Web Server 服务器重定向到入口文件。Nginx 样例如下：
```
root /data1/www/htdocs/service.movie.weibo.com/public/;
if ( !-f $request_filename ) { 
	#这里 /index.php/$1 路径要不要带 public 主要依赖配置的 root 路径是什么
    rewrite "^/(.*)" /index.php/$1 last;
}
#下边是控制静态资源访问路径，可以不要
location ~* .(css|js|img)$ {
    root /data1/www/htdocs/service.movie.weibo.com/public/;
    if (-f $request_filename) {
        expires off;
        break;
    }   
}
```
1. 路由解析规则：域名后第一个用'/'分离的部分为 module 名，最后一部分为 action 名，中间部分解析为 controller。如:
```
http://service.movie.weibo.com:8183/demo/demo/a/index?a=test&b=12  
Module： Demo  
Controller： Demo\Controllers\Demo\A  
Action: index  
参数: a 和 b
```
这里要注意，url结尾有没有"/"很关键，结尾有"/"意味着 Action 的值会解析成 index 。
1. 每个 module 可以有自己的 Bootstrap.php 在自己的根目录里，在框架初始化时会顺序执行'_init'开头的成员方法。
1. 每个 module 有自己的路由插件在 Plugins 文件夹内，可以在 Bootstrap 类中调用 Dispatcher 类的 registerPlugin 方法进行插件注册。
插件包含routerStartup、routerShutdown、dispatchStartup、dispatchShutdown、preResponse几个部分。分别为:
```
routerStartup ： 路由规则解析前  
routerShutdown ：路由规则解析后  
dispatchStartup ： 控制器分发前  
dispatchShutdown ： 控制器分发后  
preResponse ： 页面渲染结果输出前
```  
1. 每个 Controller 类必须继承 Framework\Models\Controller 。Controller 中的 Action 后缀类成员方法为可以调用的接口。  
每个接口可以定义一个对应的参数合法性检验的静态变量，静态变量名的对应规则为： "全大写的接口名_PARAM_RULES"。如 'indexAction' 的参数定义如下:
```
 $INDEX_PARAM_RULES = array(
        'a' => 'requirement', //必须有a参数
        'b' => 'number&max:15&min:10', //b参数如果存在则必须为数字且范围在10-15之间
        'c' => 'timestamp', // c参数若存在则必须为合法时间戳
        'd' => 'enum:a,1,3,5,b,12345' //d参数若存在则必须为枚举项
    );//具体参见 Framework\Libraries\Validator 类的定义
```
1. 每个 Action 若返回结果不为 False ，则会加载相应的 View 视图，视图可以混写 PHP 代码。  
在Action内可以调用 $this->assign() 方法注册渲染变量。如：$this->assign(array('text' => 'Hello,world!'));  
相应的视图加载规则： Controller名\Action名.phtml。如:
```
http://service.movie.weibo.com:8183/demo/demo/a/index?a=test&b=12 
Controller 文件路径： MODULE_ROOT\Controllers\Demo\A.php
View 文件路径：MODULE_ROOT\Views\Demo\A\index.phtml
```

#### 如何方便迁移 Module
1. 在框架层做了 Module 之间的资源隔离，不同 Module 之间无法通过 new 关键字来进行数据交换；
1. 框架提供了 LocalCurl 类，可以模拟 HTTP 网络调用，其实是在内存中完成了不同 Module 之间的数据交互；
1. 迁移的时候，执行 全局替换 LocalCurl 为 Curl 即可完成框架部分的迁移，当然业务里域名修改的地方还需要业务技术另行修改；

#### 如何进行自动化测试（单元测试）
1. 在各自 Module 下的 Tests 文件夹内创建单元测试文件，需要继承框架 Framework\Libraries\TestSuite 类；
1. 命令行下执行 php public/run_test.php 即可完成全部单元测试文件的执行。也可指定要执行的单元测试文件或 Module。如： php public/run_test.php Framework TestPDOManager.php

#### 如何进行仿真环境配置文件加载重定向
1. 在 config 下创建 {env_name} 文件夹, {env_name} 名字任意，'pro' 为保留的关键字，视为生产环境标志。文件夹内的配置文件命名同正式的配置文件名即可; 
1. ConfigTool 加载配置文件时，会依次判断静态变量 $_env 、 $_COOKIE['VISIT_SERVER_ENV'] 和 $_SERVER['VISIT_SERVER_ENV']，如有设置环境名，则启用相应环境的配置文件夹下的同名配置文件。
1. \Framework\Libraries\Tools::setEnv(string $env) 可以设置环境名，此时会给 静态变量 $_env 和 setcookie()。  
1. 样例参考 Sso 下的 config\dev\database.php 和 config\database.php ，分别会在仿真环境和生产环境读取。生产环境下，不会重定向到非生产环境的配置文件，Tools::getEnv() 强制返回生产环境标志,以防止cookie伪造。

#### 如何使用接口认证
 * 通过配置文件设置认证方式，安全认证字段主要包括(可以参考Sso\config\api_auth.php)：
 	* app_secret参与的签名验证；需要开启参数use_sign = true 和设置 app_secret 值
    * 白名单验证，需要设置 white_ips , 值的格式为: 10.83,10.222.69.0/27,127.0.0.1,10.210.10,10
    * 请求时间有效性验证，在app_secret参与验证的基础上增加设置 valid_time值大于0，则会进行时间验证,该值的单位时间为10s
 
 * 签名相关接收的参数为：
    * app_key  接口调用方id
    * app_sign  接口调用方加密后的签名
    * app_time  如果需要时间有效性验证，则会覆盖占用该参数，接口参数定义不要使用这个参数
 
 * app_secret 及 时间验证的签名规则：
    1. 参数数组增加签证密钥，如：$params['app_secret']=$app_secret，如果需要验证时间，则需增加 $params['app_time']= intval(time() / 10);
    2. 把参数数组构建为无下标的新数组,如： $tmp = array('param_a=1','param_b=stringxxx','app_secret=xxx')
    3. 对新数组进行按字母生序排序,如： sort($tmp);
    4. 使用字符"&"合并排序后的数组生成字符串,如： $params_str = implode('&',$tmp);
    5. 使用md5获取哈希值，取前6位，至此获得参数的签名字符串,如： $sign = substr(md5($params_str), 0, 6);

#### 如果进行性能优化
1. 在想进行代码优化的开始位置执行以下代码：
``` 
$lc = new LocalCurl();
$lc->setAction('test', 'http://127.0.0.1/xhprof/xhprof/run');
```
1. 随后在进程结束后会给出查看程序执行细节profile的链接，点击查看即可。

#### 如果使用框架提供的异常处理服务
1. 参考Sso\Bootstrap.php 初始化函数：
``` 
 public function _initControllerExceptionHandler(Dispatcher $dispatcher) {
        $dispatcher->registerExceptionHandle('\Framework\Models\ControllerException', function ($exception) {
            ApiDisplay::display(ApiDisplay::PARAM_ERROR_CODE, array($exception->getMessage()));
            return true;
        });
}
```
1. 告诉框架自己想要处理的异常 "\Framework\Models\ControllerException" 以及处理该异常的匿名函数即可。
1. 这里有一点需要注意，匿名函数可以有返回值告诉框架，这个异常是否处理成功，如果返回 false ，则框架认为未处理成功，会继续抛出这个异常。
