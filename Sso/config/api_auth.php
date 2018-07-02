<?php
define('ADMIN_SSO_API_AUTH_KEY', 10000);
return array(
    ADMIN_SSO_API_AUTH_KEY => array(
        'app_key'    => ADMIN_SSO_API_AUTH_KEY,
        'app_secret' => 'testsecret',
        'white_ips'  => '10.83,10.222.69.0/27,127.0.0.1,10.210.10,10.222',
        'use_sign'   => true,
        'valid_time' => 0 //1个单位时间有效期(10s),如果为2则为两个单位时间有效期，0表示不验证有效时间。
    )
);