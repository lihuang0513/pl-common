<?php

use CompanyCommon\Library\Autoloader;

// 只注册公共命名空间，配置、数据库和 Redis 均在实际使用时加载
require_once sprintf('%s/src/library/Autoloader.php', __DIR__);
Autoloader::register();
