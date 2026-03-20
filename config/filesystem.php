<?php

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() ,
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/static',
            // 磁盘路径对应的外部URL路径
            'url'        => '/static',
            // 可见性
            'visibility' => 'public',
        ],
        'ftp' => [
            'type'     => 'ftp',
            'host'     => '192.168.158.220', // FTP 服务器域名或 IP 地址
            'port'     => 21, // FTP 端口，默认为 21
            'username' => 'yuszjpapp', // FTP 登录用户名
            'password' => '852#Mhd*Wd87', // FTP 登录密码
            'root'     => '/root', // FTP 根目录
            'passive'  => true, // 被动模式，当你在 shared 主机上时可以设置为 true
            'ssl'      => false,
            'timeout'  => 30, // 连接超时时间
            // 更多的 FTP 参数根据需要添加
        ]
        // 更多的磁盘配置信息
    ],
];
