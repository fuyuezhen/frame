<?php
return [
    'http' => [
        'host' => '0.0.0.0',
        'port' => 9000,
        'tcpable' => 1, // 是否开启tcp监听，1为开启，0为关闭
        'swoole' => [
            'task_worker_num' => 0
        ],
        // RPC用于监听不同host下的不同端口。比如一个应用服务器，可能需要监听外网http服务端口，同时也监听内网的管理端口
        // 场景：比如直播，我给不同的主播分配不同的端口，主播通过TCP协议连接服务器，同事服务端又监听粉丝端HTTP请求。这样管理员可以通过关闭主播tcp连接，并且反馈给http粉丝端。
        'rpc' => [ 
            'host' => "127.0.0.1",
            'port' => 8000,
            'swoole_setting' => [
                'worker_num' => 2
            ]
        ]
    ],
    'ws' => [
        'is_handshake' => true, // 是否自定义handshake 握手的过程
        'host' => '0.0.0.0',
        'port' => 9000,
        'tcpable'     => 1,    // 是否开启tcp监听，1为开启，0为关闭
        'enable_http' => true, // 是否开启http服务
        // RPC用于监听不同host下的不同端口。比如一个应用服务器，可能需要监听外网http服务端口，同时也监听内网的管理端口
        // 场景：比如直播，我给不同的主播分配不同的端口，主播通过TCP协议连接服务器，同事服务端又监听粉丝端HTTP请求。这样管理员可以通过关闭主播tcp连接，并且反馈给http粉丝端。
        'rpc' => [ 
            'host' => "127.0.0.1",
            'port' => 8000,
            'swoole_setting' => [
                'worker_num' => 2
            ]
        ],
        'swoole' => [
            'task_worker_num' => 0
        ]
    ],
    'route' => [
        'server' => [
            'host' => '192.168.218.30',
            'port' => 9000
        ],
        'jwt' => [
            'key' => 'swocloud',
            'alg' => ['HS256']
        ]
    ]

];