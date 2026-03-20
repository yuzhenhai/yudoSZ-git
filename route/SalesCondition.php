<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 15:17
 */

use think\facade\Route;

Route::post('salesCondition', 'modules.businessInfo.SalesCondition/index');
Route::post('salesCondition-detail', 'modules.businessInfo.SalesCondition/detail');