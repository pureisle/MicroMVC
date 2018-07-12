<?php
define('ADMIN_SSO_API_AUTH_KEY', 10000);
return array(
    ADMIN_SSO_API_AUTH_KEY => array(
        'app_key'    => ADMIN_SSO_API_AUTH_KEY,
        'app_secret' => 'testsecret',
        'white_ips'  => '127.0.0.1,10,172',
        'use_sign'   => false,
        'valid_time' => 0 //1个单位时间有效期(10s),如果为2则为两个单位时间有效期，0表示不验证有效时间。
    )
);