<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-05 15:15
 */

use think\facade\Route;

Route::group('MarketAnalysis', function () {
    Route::post('lists', 'modules.salesManage.MarketAnalysis/lists');
    Route::post('detail', 'modules.salesManage.MarketAnalysis/detail');
});