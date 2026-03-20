<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-04 13:14
 */

use think\facade\Route;

Route::group('proDelMan', function () {
    Route::post('lists', 'modules.salesBusiness.prodDelMan/lists');
    Route::post('detail', 'modules.salesBusiness.prodDelMan/detail');
    Route::post('info', 'modules.salesBusiness.prodDelMan/info');
});