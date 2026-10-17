<?php

// 复制为 local.php 后填写自己的本地配置，禁止将真实凭据提交到 Git
return [
    'database' => [
        'default' => [
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=pinglun;charset=utf8mb4',
            'username' => '',
            'password' => '',
            'timeout' => 3,
        ],
    ],
    'redis' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'database' => 0,
            'timeout' => 1.0,
            'read_timeout' => 1.0,
            'prefix' => 'pl-common:',
        ],
    ],
];
