### http请求类
功能：
1. 对原有curl扩展进行了封装，使用更方便。
1. 可以自动转化为命令行 curl ，以便联调测试。
1. 方便的进行并发请求。	

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- Curl.php 	http请求类
		|- MultiCurl.php 	并发http请求类
```

### 使用方法
1. Curl 使用样例如下：
```
$manager = new Curl ();
$manager->setAction ( 'lg', 'http://wappass.weibo.com/', 'http://t.cn' )
        ->cookie ()->post ( 'lg', $data );
var_dump ( $manager->header () );
var_dump ( $manager->body () );
```
通过以上方式可以方便的发起一个 POST 请求，并且可以记录返回的 Cookie。
如果想要获取命令行的转化，可以在发起 get() 或 post() 等方法之前的任意位置调用 delayExec() 方法，然后在 get() 或 post() 方法之后调用 getSheelCurl() 即可。  
其他如 SSL 、跳转递归访问等特性请看代码提供的方法列表。
1. MultiCurl 类的使用样例如下：
```
$mc = new MultiCurl();
$c1 = new Curl();
$c1->setAction('tmp', 'http://t.cn/fsgerge');
$c2 = new Curl();
$c2->setAction('tmp', 'http://t.cn/afwaefw');
$c3 = new Curl();
$c3->setAction('tmp', 'http://t.cn/awefwef');

$mc->addCurl(array($c1, $c2, $c3))->get('tmp')->exec();
var_dump($c1->body(),$c2->body(),$c3->body());
```
每一个 Curl 类的实例化依然如单 HTTP 请求类使用相同，每个 Curl 实例设置完相应的参数后，需要通过 MultiCurl->addCurl() 方法去注册，然后执行 MultiCurl->exec() 方法即可完成并发 HTTP 请求。  
特别的，需要注意的是 MultiCurl 类提供了批量操作已注册的 Curl 实例的魔术方法。如上例中的 $mc->get('tmp') ,即为批量的设置已经注册的 Curl 实例相应的请求设置为 GET 方式。类似的，也可以批量的设置 timeOutForConnect()、setHeader() 等。