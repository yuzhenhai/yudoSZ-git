<?php
/**
 * @Author: Yuzh
 * @Date: 2024-10-23 15:16
 */

use think\facade\Route;


// Route::post('Langs', 'index/LangFile');//多语言生成
//安装试模
Route::post('InstallationTrial-index', 'modules.salesBusiness.InstallationTrial/index');
Route::post('InstallationTrial-address', 'modules.salesBusiness.InstallationTrial/address');//到达地点录入
Route::post('InstallationTrial-getAddress', 'modules.salesBusiness.InstallationTrial/getAddress');//获取到达地点录入
Route::post('InstallationTrial-installSeach', 'modules.salesBusiness.InstallationTrial/installSeach');//安装查询
Route::post('InstallationTrial-TestModelSeach', 'modules.salesBusiness.InstallationTrial/TestModelSeach');//试模查询
Route::post('InstallationTrial-Code', 'modules.salesBusiness.InstallationTrial/getSCode');//试模查询
Route::post('OrderSeach', 'modules.salesBusiness.InstallationTrial/OrderSeach');//订单列表查询
Route::post('orderMinute', 'modules.salesBusiness.InstallationTrial/orderMinute');//订单查询
Route::post('SystemclassBigPrc', 'modules.salesBusiness.InstallationTrial/SystemclassBigPrc');//系统大分类查询
Route::post('SystemMiniClass', 'modules.salesBusiness.InstallationTrial/SystemMiniClass');//系统小分类查询
Route::post('getEmpyList', 'modules.salesBusiness.InstallationTrial/getEmpyList');//职员列表
Route::post('getInfoxinxi', 'modules.salesBusiness.InstallationTrial/getInfoxinxi');//职员列表
Route::post('getArrival', 'modules.salesBusiness.InstallationTrial/getArrival');//当日到达地址
Route::post('LeaveAddress', 'modules.salesBusiness.InstallationTrial/LeaveAddress');//到达地点录入
Route::post('InstallSave', 'modules.salesBusiness.InstallationTrial/InstallSave');//保存安装报告
Route::post('AddSales', 'modules.salesBusiness.InstallationTrial/addSales');//添加同行人员
Route::post('DeleteSales', 'modules.salesBusiness.InstallationTrial/DeleteSales');//添加同行人员
Route::post('SalesList', 'modules.salesBusiness.InstallationTrial/SalesList');//同行人员列表
Route::post('getAssmPhotoNm', 'modules.salesBusiness.InstallationTrial/getAssmPhotoNm');//图片列表
Route::post('DeletePhotos', 'modules.salesBusiness.InstallationTrial/DeletePhotos');//删除照片
Route::post('addPhotos', 'modules.salesBusiness.InstallationTrial/addPhotos');//添加照片
Route::post('SignImage', 'modules.salesBusiness.InstallationTrial/SignImage');//添加签名文件
Route::post('SubAdjudication', 'modules.salesBusiness.InstallationTrial/SubAdjudication');//安装报告-提交OA审核
Route::post('UnSubAdjudication', 'modules.salesBusiness.InstallationTrial/UnSubAdjudication');//安装报告-提交OA审核

Route::post('InstallPDF', 'modules.salesBusiness.InstallationTrial/InstallPDF');//安装报告-生成PDF
Route::post('SendEmail', 'modules.salesBusiness.InstallationTrial/SendEmail');//安装报告-生成PDF



//
//试模
//
Route::post('OrderPartition', 'modules.salesBusiness.TestationTrial/OrderPartition');//试模报告-订单区分
Route::post('OrderGeneralSearch', 'modules.salesBusiness.TestationTrial/OrderGeneralSearch');//试模报告-订单搜索
Route::post('orderMinuteTest', 'modules.salesBusiness.TestationTrial/orderMinuteTest');//试模报告-订单查询
Route::post('getCustList', 'modules.salesBusiness.TestationTrial/getCustList');//试模报告-客户查询
Route::post('saveTstInj', 'modules.salesBusiness.TestationTrial/saveTstInj');//试模报告-保存
Route::post('TestInfo', 'modules.salesBusiness.TestationTrial/TestInfo');//试模报告-详情
Route::post('addTastSales', 'modules.salesBusiness.TestationTrial/addTastSales');//试模报告-添加同行人员
Route::post('DeleteTastSales', 'modules.salesBusiness.TestationTrial/DeleteTastSales');//试模报告-删除同行人员
Route::post('getTestPhotoNm', 'modules.salesBusiness.TestationTrial/getTestPhotoNm');//试模报告-图片列表

Route::post('addTestPhotos', 'modules.salesBusiness.TestationTrial/addTestPhotos');//试模报告-添加
Route::post('DeleteTastPhone', 'modules.salesBusiness.TestationTrial/DeleteTastPhone');//试模报告-删除照片
Route::post('TestsubAdjudication', 'modules.salesBusiness.TestationTrial/TestsubAdjudication');//试模报告-OA审核
Route::post('unTestSubAdjudication', 'modules.salesBusiness.TestationTrial/unTestSubAdjudication');//试模报告-取消OA审核


