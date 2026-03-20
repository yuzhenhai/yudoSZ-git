<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 15:13
 */

use think\facade\Route;

Route::post('saveGoal','modules.salesBusiness.salesGoal/saveGoal');
Route::post('salesGoal-confirm','modules.salesBusiness.salesGoal/confirm');
Route::post('salesGoal-salesLeaders','modules.salesBusiness.salesGoal/salesLeaders');
Route::post('salesGoal-getMempId','modules.salesBusiness.salesGoal/getMempId');
Route::post('salesGoal-getDeptId','modules.salesBusiness.salesGoal/getDeptId');
Route::post('salesGoal-getResult','modules.salesBusiness.salesGoal/getResult');
Route::post('salesGoal-getPremission','modules.salesBusiness.salesGoal/getPremission');
Route::post('test','modules.salesBusiness.salesGoal/test');