<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-31 14:01
 */

use think\facade\Route;

Route::group('yearOver', function () {
    Route::post('lists', 'modules.salesManage.YearOverYear/lists');
    Route::post('detail', 'modules.salesManage.YearOverYear/detail');
    Route::post('deptClass', 'modules.salesManage.YearOverYear/getDeptClass');
    Route::get('dept', 'modules.salesManage.YearOverYear/getDept');
});