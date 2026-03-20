<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-22 8:31
 */

use think\facade\Route;

Route::group('DesignManage', function () {
    Route::post('Daily', 'modules.designManage.DailyData/index');
    Route::post('lists', 'modules.designManage.DailyData/lists');
});