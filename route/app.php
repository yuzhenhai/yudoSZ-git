<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 引入当前文件夹内除了 app.php 以外的其他所有 PHP 文件
foreach (glob(__DIR__ . '/*.php') as $file) {
    if (basename($file) !== 'app.php') {
        include_once $file;
    }
}

Route::post('login', 'User/login');
Route::post('login2', 'User/login2');
Route::post('getUsers','User/getUsers');
Route::get('getLanguage','User/getLanguage');





