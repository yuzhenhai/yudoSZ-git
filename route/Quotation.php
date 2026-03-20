<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-09 9:00
 */

use think\facade\Route;

Route::group('quotation', function () {
    Route::post('lists', 'modules.salesBusiness.quotation/lists');
    Route::post('quotInfo', 'modules.salesBusiness.quotation/quotInfo');
    Route::post('getQuoteItemList', 'modules.salesBusiness.quotation/getQuoteItemList');
    Route::post('selectList', 'modules.salesBusiness.quotation/selectList');
    Route::post('selectListSync', 'modules.salesBusiness.quotation/selectListSync');
    Route::post('saveQuote', 'modules.salesBusiness.quotation/saveQuote');
    Route::post('getCurrRate', 'modules.salesBusiness.quotation/getCurrRate');
    Route::post('getQuoteJobPower', 'modules.salesBusiness.quotation/getQuoteJobPower');
    Route::post('getItems', 'modules.salesBusiness.quotation/getItems');
    Route::post('getStdPrice', 'modules.salesBusiness.quotation/getStdPrice');
    Route::post('addServiceCharge', 'modules.salesBusiness.quotation/addServiceCharge');
    Route::post('confirm', 'modules.salesBusiness.quotation/confirm');
    Route::post('getCustInfo', 'modules.salesBusiness.quotation/getCustInfo');
    Route::post('save', 'modules.salesBusiness.quotation/save');
});