<?php

// 复制为 local.php 后填写自己的本地配置，禁止将真实凭据提交到 Git
return [
    'database' => [
        'pinglun' => [
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=pinglun;charset=utf8mb4',
            'username' => '',
            'password' => '',
            'timeout' => 3,
        ],
        // 在本地配置中替换为目标测试服务器地址，真实凭据不得提交
        'plapi_log' => [
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=plapi;charset=utf8mb4',
            'username' => '',
            'password' => '',
            'timeout' => 3,
        ],
    ],
    'redis' => [
        'local' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'database' => 0,
            'timeout' => 1.0,
            'read_timeout' => 1.0,
            // 验证接口按用户要求操作原始键 test，不加公共业务键前缀
            'prefix' => '',
        ],
    ],
];
