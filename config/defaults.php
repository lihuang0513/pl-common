<?php

// 默认不提供数据库凭据，调用方通过独立本地配置指定实际资源
return [
    'database' => [
        'default' => [
            'dsn' => '',
            'username' => '',
            'password' => '',
            'timeout' => 3,
        ],
    ],
    'redis' => [
        'default' => [
            'host' => '',
            'port' => 6379,
            'password' => '',
            'database' => 0,
            'timeout' => 1.0,
            'read_timeout' => 1.0,
            'prefix' => 'pl-common:',
        ],
    ],
];
