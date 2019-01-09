### 安全相关
框架主要提供如下安全机制：
1. 防SQL注入
1. 防XSS
1. 防CSRF
1. 请求合法性校验
1. 依赖浏览器的HTTP协议安全相关的报头

### 相关文件
```
|- Framework	框架
	|- Models
		|- Controller.php 	控制器基类
		|- ControllerAuth.php 	控制器安全类
		|- PDOManager.php 	PDO 扩展封装
	|- Libraries	框架类库
		|- ControllMysql.php 	SQL生产工具
```

### 使用方法
1. 防 SQL 注入。对数据库的操作如果继承了父类 Framework\Libraries\ControllMysql 类，并且没有使用特别的自己拼写 SQL 的用法，则基本上不用担心 SQL 注入问题。底层实现方式主要是依赖 PDO 扩展的 prepare() 方法。
1. 防 XSS 。在 Controller 基类里，下发渲染数据时需要使用 assign(array $var_arr, bool $is_html_encode = true) 方法，该方法提供了默认的 HTML 实体转译的操作，防止下发的渲染数据出现 XSS 漏洞。
1. 防 CSRF 。 该漏洞一般发生上行接口且上行接口前一页是表单页。目前常见做法是增加验证码。框架的做法是在前置页使用 Controller 基类提供的 csrfSet(string $host = '') 方法，该方法会下发一个框架 cookie ，然后在后职页使用 csrfCheck() 方法，会去检查相应 cookie 是否存在，以此来防治 CSRF 漏洞的出现。
1. 请求合法性验证。 具体使用 Controller 父类的 useAuth(string $auth_config) 方法，其中 $auth_config 参数为配置文件名称，具体可以参考 Sso 项目下的 api_auth.php 配置。框架提供的多种验证方式，如下：
```
安全认证主要包括：
     *     1、app_secret参与的签名验证；需要开启参数use_sign = true 和设置 app_secret 值
     *     2、白名单验证，需要设置 white_ips , 值的格式为: 10.83,10.222.69.0/27,127.0.0.1,10.210.10,10
     *     3、请求时间有效性验证，在app_secret参与验证的基础上增加设置 valid_time值大于0，则会进行时间验证,该值的单位时间为10s
     *
     * 签名相关接收的参数为：
     *     app_key  接口调用方id
     *     app_sign  接口调用方加密后的签名
     *     app_time  如果需要时间有效性验证，则会覆盖占用该参数，接口参数定义不要使用这个参数
     *
     * app_secret 及 时间验证的签名规则：
     *     1、参数数组增加签证密钥，如：$params['app_secret']=$app_secret，如果需要验证时间，则需增加 $params['app_time']= intval(time() / 10);
     *     2、把参数数组构建为无下标的新数组,如： $tmp = array('param_a=1','param_b=stringxxx','app_secret=xxx')
     *     3、对新数组进行按字母生序排序,如： sort($tmp);
     *     4、使用字符"&"合并排序后的数组生成字符串,如： $params_str = implode('&',$tmp);
     *     5、使用md5获取哈希值，取前6位，至此获得参数的签名字符串,如： $sign = substr(md5($params_str), 0, 6);
```
1. 框架的控制器基类 Controller 提供了一些基于浏览器实现的HTTP协议报头，这些报头需要使用者深入了解其机制后使用，以避免不必要的安全损失,方法如下:
```
forceHTTPS(int $sec = 319550916, string $include_sub_domain = '') 强制浏览器访问HTTPS协议的地址
useFrame(string $opt = 'SAMEORIGIN')	禁止当前页面被其他 iframe 嵌套
disableSniffing()	禁止浏览器自动探测文件类型
useXSS($enable = 1)	使用浏览器提供的XSS检测和防护
usePolicy(string $policy_urls_config = 'policy_urls') 告知浏览器可引用的源地址，参数为配置文件名，具体可参考 Sso 项目里的配置文件 policy_ursl.php
```

以上的一些安全策略，还需要在实际项目中实际看业务需要，判断该如何选用或另行实现，框架只是提供一个比较通用做法。