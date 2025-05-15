<?php

use think\queue\driver\Redis;

return [
    'default' => 'redis',
    'connections' => [
        'redis' => [
            'connector' => Redis::class,
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'select' => 0,
            'timeout' => 0,
            'persistent' => false,
            'expire' => 60,
            'prefix' => '',
            'serialize' => true,
        ],
    ],
];
