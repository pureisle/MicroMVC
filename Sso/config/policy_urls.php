<?php
return array(
    //定义针对所有类型（js、image、css、web font，ajax 请求，iframe，多媒体等）资源的默认加载策略，某类型资源如果没有单独定义策略，就使用默认的。
    'default-src' => array('*'),
    'script-src'  => array("'self'", 'http://*.weibo.com', '*.weibo.cn'), //定义针对 JavaScript 的加载策略。
    'style-src'   => array(),                                             //定义针对样式的加载策略。
    'img-src'     => array("'none'"),                                     //定义针对图片的加载策略。
    'connect-src' => array(),                                             //针对 Ajax、WebSocket 等请求的加载策略。不允许的情况下，浏览器会模拟一个状态为 400 的响应。
    'font-src'    => array(),                                             //针对 WebFont 的加载策略。
    'object-src'  => array(),                                             //针对 <object>、<embed> 或 <applet> 等标签引入的 flash 等插件的加载策略。
    'media-src'   => array(),                                             //针对 <audio> 或 <video> 等标签引入的 HTML 多媒体的加载策略。
    'frame-src'   => array(),                                             //针对 frame 的加载策略。
    'sandbox'     => array(),                                             //对请求的资源启用 sandbox（类似于 iframe 的 sandbox 属性）。

    //告诉浏览器如果请求的资源不被策略允许时，往哪个地址提交日志信息。 特别的：如果想让浏览器只汇报日志，不阻止任何内容，可以改用 Content-Security-Policy-Report-Only 头。
    'report-uri'  => array()
);

/*
指令值可以由下面这些内容组成：
指令值    指令示例    说明
img-src    允许任何内容。
'none'    img-src 'none'    不允许任何内容。
'self'    img-src 'self'    允许来自相同来源的内容（相同的协议、域名和端口）。
data:    img-src data:    允许 data: 协议（如 base64 编码的图片）。
www.a.com    img-src img.a.com    允许加载指定域名的资源。
.a.com    img-src .a.com    允许加载 a.com 任何子域的资源。
https://img.com    img-src https://img.com    允许加载 img.com 的 https 资源（协议需匹配）。
https:    img-src https:    允许加载 https 资源。
'unsafe-inline'    script-src 'unsafe-inline'    允许加载 inline 资源（例如常见的 style 属性，onclick，inline js 和 inline css 等等）。
'unsafe-eval'    script-src 'unsafe-eval'    允许加载动态 js 代码，例如 eval()。
 */