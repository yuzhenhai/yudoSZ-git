<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-04 9:34
 */

use think\facade\Route;

Route::group('yearlyMarkets', function () {
    Route::post('lists', 'modules.salesManage.YearlyMarkets/lists');
    Route::post('detail', 'modules.salesManage.YearlyMarkets/detail');
});