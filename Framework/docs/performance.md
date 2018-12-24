Yaf是用C语言写的PHP扩展，在所有MVC框架里，性能做到了很好的程度。关于Yaf的性能测试见[yaf性能测试](http://www.laruence.com/2011/12/02/2333.html)。

在工程项目里，为了项目上的稳定性、安全性和可扩展性上，牺牲少量性能是可以接受。相对于简单的Yaf和其他复杂大型的MVC框架，MicroMVC是一个很好的合适业务开发的折中选择。

测试内容及环境：
在本地启动docker环境，搭建nginx服务，在同一项目目录里同时写入使用yaf框架的代码文件和使用MicroMVC框架的代码文件。
代码文件均为IndexController,仅含一个indexAction(),该方法内仅有一句 echo 输出。

压测工具：ab
监视工具：netdata

CPU对比，MicroMVC比Yaf多了大概不足10%的性能占用。
![CPU性能对比](http://wx2.sinaimg.cn/large/3eab3a68ly1fxfr4rf6xxj20u20dimz0.jpg)

压测对比，MicroMVC比Yaf，有大概15%以内的性能下降。
![对比1](http://wx1.sinaimg.cn/large/3eab3a68ly1fxfr4rghtuj20v40s0tby.jpg)
![对比2](http://wx1.sinaimg.cn/large/3eab3a68ly1fxfr4rhwouj20u00sun0m.jpg)



