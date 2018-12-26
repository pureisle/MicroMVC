### 参数检查

### 相关文件
```
|- Framework	框架
	|- Libraries	框架类库
		|- Validator.php 	参数检查类
	|- Models 
		|- Controller.php 	控制器基类
		|- Request.php 		访问请求类
```

### 使用方法
1. 构建参数检测规则，如下：
```
$rule_set = array(
	'a' => 'requirement',
	'b' => 'number&max:15&min:10',
	'c' => 'timestamp',
	'd' => 'enum:a,1,3,5,b,12345'
);
```
1. 使用检测器检查, 如下：
```
$params=array('a'=>xxx,'b'=>xxx);
$v = new Validator();
$ret = $v->check($params, $rule_set);
$v->getErrorMsg();
```
### 框架里获取参数的方法
1. 框架在底层做了web接口的强制参数检查，就是如果不按框架的要求进行参数设置和检查，则无法通过 $_GET 或 $_POST 获取请求参数。
1. 在框架下获取http请求的参数方式如下：
```
class ControllerA extends Controller{
    public static $INDEX_PARAM_RULES = array(
        'name'   => 'requirement&not_empty',
        'passwd' => 'requirement&not_empty',
        'email'  => '',
        'tel'    => '',
        'extend' => ''
    );
    public function indexAction() {
        $params = $this->getGetParams();
        $params = $this->getPostParams();
        $params = $this->getJsonParams();
    }
}
```
1. Controller 内的每个 Action，如果想获取 http 的请求参数，都需要按上述代码定义一个参数规则集，静态变量命名规则为 大写的action名称_PARAM_RULES ，该数据定义的检查类型可以具体看 Validator.php 中的代码 private static $KEY_WORD 的定义。
1. 然后可以使用框架提供的 getGetParams、 getPostParams 和 getJsonParams 三个方法获取相应的参数。
1. 如果参数不合法，则会抛出异常: throw new ControllerException(ControllerException::ERROR_PARAM_CHECK); 需要业务使用者自行捕获一场进行处理。
1. 如果前端接口异常处理想统一处理或同一输出，可以先写一个继承框架的 Controller 的类，然后自己业务的控制器去继承业务的Controller类即可。