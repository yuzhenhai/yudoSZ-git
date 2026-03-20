<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 11:42
 */

use think\facade\Route;

Route::group('purchase', function () {
    Route::post('lists', 'modules.purchaseManage.purchase/lists');
    Route::post('detail', 'modules.purchaseManage.purchase/detail');
    Route::post('getSelectList', 'modules.purchaseManage.purchase/getSelectList');
    Route::post('stats', 'modules.purchaseManage.purchase/stats');
    Route::post('statsDetail', 'modules.purchaseManage.purchase/statsDetail');
});