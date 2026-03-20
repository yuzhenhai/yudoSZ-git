<?php
use think\facade\Route;

Route::post('RecvSearch', 'modules.salesBusiness.RecvHandle/RecvSearch');//AS申请查询

Route::post('getCustZZList', 'modules.salesBusiness.RecvHandle/getCustZZList');//注塑厂名称和最终客户
Route::post('getCustYJList', 'modules.salesBusiness.RecvHandle/getCustYJList');//一级供应商
Route::post('getDeptsList', 'modules.salesBusiness.RecvHandle/getDeptsList');//移模部门
Route::post('getASMinute', 'modules.salesBusiness.RecvHandle/getASMinute');//AS详情

Route::post('getASSave', 'modules.salesBusiness.RecvHandle/getASSave');//AS保存
Route::post('getOrderMinute', 'modules.salesBusiness.RecvHandle/getOrderMinute');//AS订单信息

Route::post('addRecvSales', 'modules.salesBusiness.RecvHandle/addRecvSales');//AS 添加同行人员
Route::post('DeleteRecvSales', 'modules.salesBusiness.RecvHandle/DeleteRecvSales');//AS 删除同行人员
Route::post('getRecvPhotoNm', 'modules.salesBusiness.RecvHandle/getRecvPhotoNm');//AS 获取图片列表
Route::post('addRecvPhotos', 'modules.salesBusiness.RecvHandle/addRecvPhotos');//AS 添加图片列表
Route::post('DeleteRecvPhone', 'modules.salesBusiness.RecvHandle/DeleteRecvPhone');//AS 删除图片列表
Route::post('ASMinuteTable', 'modules.salesBusiness.RecvHandle/ASMinuteTable');//AS 品目列表
Route::post('ASUnit', 'modules.salesBusiness.RecvHandle/ASUnit');//AS 品目单位
Route::post('getItemList', 'modules.salesBusiness.RecvHandle/getItemList');//AS 品目查询
Route::post('getItem', 'modules.salesBusiness.RecvHandle/getItem');//查看单条AS 品目查询
Route::post('saveAsItem', 'modules.salesBusiness.RecvHandle/saveAsItem');//AS 品目查询
Route::post('DeleteRecvItem', 'modules.salesBusiness.RecvHandle/DeleteRecvItem');//AS 品目删除
Route::post('getSpecList', 'modules.salesBusiness.RecvHandle/getSpecList');//AS 技术规范
Route::post('RecvSignImage', 'modules.salesBusiness.RecvHandle/RecvSignImage');//AS 签名
Route::post('RecvPDF', 'modules.salesBusiness.RecvHandle/RecvPDF');//AS PDF
Route::post('RecvSubAdjudication', 'modules.salesBusiness.RecvHandle/subAdjudication');//AS OA审核
Route::post('RecvUnSubAdjudication', 'modules.salesBusiness.RecvHandle/unSubAdjudication');//AS OA审核

Route::post('UpdateDw', 'modules.salesBusiness.RecvHandle/UpdateDw');//有无接受权限
Route::post('ASTDEDwReq', 'modules.salesBusiness.RecvHandle/ASTDEDwReq');//图纸依赖
Route::post('SaveDw', 'modules.salesBusiness.RecvHandle/SaveDw');//保存图纸依赖
Route::post('UpdateAS', 'modules.salesBusiness.RecvHandle/UpdateAS');//AS申请 有无AS接受






Route::post('getAsHandle', 'modules.salesBusiness.RecvHandle/getAsHandle');//AS处理
Route::post('ASHandlePrc', 'modules.salesBusiness.RecvHandle/ASHandlePrc');//AS处理 AS编码查询
Route::post('getASKindList', 'modules.salesBusiness.RecvHandle/getASKindList');//AS处理 AS类型列表
Route::post('getASProcKindList', 'modules.salesBusiness.RecvHandle/getASProcKindList');//AS处理区分列表
Route::post('getASProcResultList', 'modules.salesBusiness.RecvHandle/getASProcResultList');//AS处理结果列表
Route::post('getItemReturnList', 'modules.salesBusiness.RecvHandle/getItemReturnList');//部品返还区分列表
Route::post('LoginUser', 'modules.salesBusiness.RecvHandle/LoginUser');//职员信息
Route::post('SaveHandle', 'modules.salesBusiness.RecvHandle/SaveHandle');//保存AS处理
Route::post('getAsHandleInfo', 'modules.salesBusiness.RecvHandle/getAsHandleInfo');//AS处理详情查询
Route::post('addHandleSales', 'modules.salesBusiness.RecvHandle/addHandleSales');//AS处理 添加同行人员
Route::post('DeleteHandleSales', 'modules.salesBusiness.RecvHandle/DeleteHandleSales');//AS处理 删除同行人员
Route::post('ASclPhoto', 'modules.salesBusiness.RecvHandle/ASclPhoto');//AS处理 照片列表
Route::post('addHandlePhotos', 'modules.salesBusiness.RecvHandle/addHandlePhotos');//AS处理 添加照片
Route::post('DeleteHandlePhone', 'modules.salesBusiness.RecvHandle/DeleteHandlePhone');//AS处理 删除照片
Route::post('ASHandleTable', 'modules.salesBusiness.RecvHandle/ASHandleTable');//AS处理 品目
Route::post('getHandleItem', 'modules.salesBusiness.RecvHandle/getHandleItem');//AS处理 品目
Route::post('saveHandleItem', 'modules.salesBusiness.RecvHandle/saveHandleItem');//AS处理 保存品目
Route::post('HandleSignImage', 'modules.salesBusiness.RecvHandle/HandleSignImage');//AS处理 签名
Route::post('DowASPDF', 'modules.salesBusiness.RecvHandle/DowASPDF');//AS处理 签名
Route::post('HandleAdjudication', 'modules.salesBusiness.RecvHandle/HandleAdjudication');//AS处理 OA审核
Route::post('UnHandleAdjudication', 'modules.salesBusiness.RecvHandle/UnHandleAdjudication');//AS处理 取消OA审核

Route::post('updataASREcv', 'modules.salesBusiness.RecvHandle/updataASREcv');//AS处理 取消OA审核


