<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-29 8:37
 */

use think\facade\Route;

Route::group('UnmetPerformance', function () {
    Route::post('lists', 'modules.salesManage.UnmetPerformance/lists');
});

