### 为什么用
* 提供最简单基础的 MVC 框架，将性能损耗降到最低
* 按 Module 进行资源分离，以便对业务进行微服务化隔离与便捷迁移
* 提供简单好用的单元测试框架
* 提供便捷的接口参数合法性验证服务
* 提供简单好用的 Mysql 控制服务

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
|- Demo	样例应用
	|- Cache	缓存管理文件
	|- config	应用配置文件（mysql、redis等资源配置；api接口配置等;不能变动）
	|- Controllers	控制器（不能变动）
	|- Models	业务逻辑类 
	|- Data 	数据访问类
	|- Entities 业务实体类
	|- Views	视图文件（不能变动）
	|- Plugins	插件文件
	|- Libraries	类库文件
	|- Tests	单元测试文件（不能变动）
	|- Bootstrap.php 	应用启动初始化文件（不能变动,可以没有）
```

### 开始使用
1. 路由解析规则：域名后第一个用'/'分离的部分为 module 名，最后一部分为 action 名，中间部分解析为 controller。如:
```
http://service.movie.weibo.com:8183/demo/demo/a/index?a=test&b=12  
Module： Demo  
Controller： Demo\Controllers\Demo\A  
Action: index  
参数: a 和 b
```
2. 每个 module 可以有自己的 Bootstrap.php 在自己的根目录里，在框架初始化时会顺序执行'_init'开头的成员方法。
3. 每个 module 有自己的路由插件在 Plugins 文件夹内，可以在 Bootstrap 类中调用 Dispatcher 类的 registerPlugin 方法进行插件注册。
插件包含routerStartup、routerShutdown、dispatchStartup、dispatchShutdown、preResponse几个部分。分别为:
```
routerStartup ： 路由规则解析前  
routerShutdown ：路由规则解析后  
dispatchStartup ： 控制器分发前  
dispatchShutdown ： 控制器分发后  
preResponse ： 页面渲染结果输出前
```  
4. 每个 Controller 类必须继承 Framework\Models\Controller 。Controller 中的 Action 后缀类成员方法为可以调用的接口。  
每个接口可以定义一个对应的参数合法性检验的静态变量，静态变量名的对应规则为： "全大写的接口名_PARAM_RULES"。如 'indexAction' 的参数定义如下:
```
 $INDEX_PARAM_RULES = array(
        'a' => 'requirement', //必须有a参数
        'b' => 'number&max:15&min:10', //b参数如果存在则必须为数字且范围在10-15之间
        'c' => 'timestamp', // c参数若存在则必须为合法时间戳
        'd' => 'enum:a,1,3,5,b,12345' //d参数若存在则必须为枚举项
    );//具体参见 Framework\Libraries\Validator 类的定义
```
5. 每个 Action 若返回结果不为 False ，则会加载相应的 View 视图，视图可以混写 PHP 代码。  
在Action内可以调用 $this->assign() 方法注册渲染变量。如：$this->assign(array('text' => 'Hello,world!'));  
相应的视图加载规则： Controller名\Action名.phtml。如:
```
http://service.movie.weibo.com:8183/demo/demo/a/index?a=test&b=12 
Controller 文件路径： MODULE_ROOT\Controllers\Demo\A.php
View 文件路径：MODULE_ROOT\Views\Demo\A\index.phtml
```