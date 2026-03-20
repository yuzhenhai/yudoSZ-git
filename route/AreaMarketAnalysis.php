<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-06 9:01
 */

use think\facade\Route;

Route::group('AreaMarketAnalysis', function () {
    Route::post('lists', 'modules.salesManage.AreaMarketAnalysis/lists');
    Route::post('detail', 'modules.salesManage.AreaMarketAnalysis/detail');
});