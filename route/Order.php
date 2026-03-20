<?php

/**
 * @Author: YUzh
 * @Date: 2024-11-05 15:15
 */

use think\facade\Route;

Route::group('Order', function () {
    Route::post('Info', 'modules.salesBusiness.Order/Info');
    Route::post('FileURL', 'modules.salesBusiness.Order/FileURL');
    Route::post('getBadlist', 'modules.salesBusiness.Order/getBadlist');
    Route::post('phoneURL', 'modules.salesBusiness.Order/phoneURL');
    Route::post('getSalesPhoto', 'modules.salesBusiness.Order/getSalesPhoto');//组装
    Route::post('getTestPhoto', 'modules.salesBusiness.Order/getTestPhoto');//试模
    Route::post('getOrderList', 'modules.salesBusiness.Order/getOrderList');//其他

    Route::post('phoneURL2', 'modules.salesBusiness.Order/phoneURL2');

    Route::post('CeshiOrder', 'modules.salesBusiness.Ceshi/shuju');
});

