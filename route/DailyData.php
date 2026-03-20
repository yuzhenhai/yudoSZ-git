<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 15:17
 */

use think\facade\Route;

Route::post('DailyData', 'modules.businessInfo.DailyData/index');
Route::post('DailyInfo', 'modules.businessInfo.DailyData/info');