<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 15:45
 */

use think\facade\Route;

Route::group('salesStats', function () {
    Route::post('lists', 'modules.salesManage.SalesStats/lists');
    Route::post('detail', 'modules.salesManage.SalesStats/detail');
});

