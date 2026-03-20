<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-08 14:55
 * 状态码
 */

return [
    'SUCCESS' => 200,   // 正常请求成功
    'DB_INSERT_SUCCESS' => 201, // SQL数据添加成功
    'DB_UPDATE_SUCCESS' => 202, // SQL数据更新成功
    'DATA_ALREADY_UPDATED' => 203, // 数据已经更新过
    'PARAM_ERROR' => 400,  // 传递参数错误
    'AUTH_FAILURE' => 401, // 账户密码错误
    'TOKEN_NULL' => 402, // 未带token
    'TOKEN_EXPIRED' => 403, // token无效
    'RESOURCE_NOT_FOUND' => 404, // 请求接口不存在
    'DEVICE_FAILURE' => 405, // 请求接口不存在
    'SERVER_ERROR' => 500,  // 服务器报错
    'DB_OPERATION_FAIL' => 501, // 数据库执行报错
    'DB_CONNECTION_FAIL' => 502, // 无法连接数据库
    'DB_INSTALL_FAIL' => 503, // 数据添加错误
    'DB_UPDATE_FAIL' => 504,    // SQL数据更新失败
];
