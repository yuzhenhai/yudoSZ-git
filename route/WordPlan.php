<?php
/**
 * @Author: Yuzh
 * @Date: 2024-10-23 15:16
 */

use think\facade\Route;


Route::post('SearchPlan', 'modules.salesBusiness.WordPlan/SearchPlan');
Route::post('TPTMADept', 'modules.salesBusiness.WordPlan/TPTMADept');//部门列表
Route::post('queryAllMsg', 'modules.salesBusiness.WordPlan/queryAllMsg');//工作计划报告查询
Route::post('getCalenderPlan', 'modules.salesBusiness.WordPlan/getCalenderPlan');//查询工作日程，工作计划时间
Route::post('getPlanMinute', 'modules.salesBusiness.WordPlan/planMinute');//查询工作info
Route::post('addPlanPhotos', 'modules.salesBusiness.WordPlan/addPlanPhotos');//添加图片
Route::post('DeletePlanPhone', 'modules.salesBusiness.WordPlan/DeletePlanPhone');//删除图片
Route::post('savePlan', 'modules.salesBusiness.WordPlan/savePlan');//保存工作计划
Route::post('planStatus', 'modules.salesBusiness.WordPlan/planStatus');//工作计划状态确认




Route::post('reptMinute', 'modules.salesBusiness.WordPlan/reptMinute');//工作报告详情
Route::post('saveRept', 'modules.salesBusiness.WordPlan/saveRept');//保存工作报告
Route::post('reptConfirm', 'modules.salesBusiness.WordPlan/reptConfirm');//确定工作报告
Route::post('addReptPhotos', 'modules.salesBusiness.WordPlan/addReptPhotos');//确定工作报告
Route::post('DeleteReptPhone', 'modules.salesBusiness.WordPlan/DeleteReptPhone');//确定工作报告


