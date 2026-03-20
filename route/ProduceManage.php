<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 11:42
 */

use think\facade\Route;

Route::group('produce', function () {
    Route::post('getCurrList', 'modules.produceManage.produce/getCurrList');
    Route::post('dailyData', 'modules.produceManage.produce/dailyData');
    Route::post('monthData', 'modules.produceManage.produce/monthData');
    Route::post('yearData', 'modules.produceManage.produce/yearData');
    Route::post('lists', 'modules.produceManage.produce/lists');
});