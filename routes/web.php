<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
  
Route::get('/', function () {
   // return view('welcome');
    return view('auth.login');
});

Auth::routes();

// Route::get('/mail', function () {
//     return view('Emails.Sales.ChangeCostPrice');
//  });
 


// system
Route::get('/home', 'HomeController@index')->name('home');

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('/resetPass', function () {
   return view('auth.passwords.reset');
});

//zalo Groups
// Route::group(['prefix' => 'zalo'], function () {
//     Route::get('/index', 'SYS\Social_ZaloController@index');
//     Route::get('/auth','SYS\Social_ZaloController@auth');    
// });

//zalo Group
Route::group(['prefix' => 'zalo'], function () {
    Route::get('/index', 'SYS\ZaloController@index');
    Route::get('/auth','SYS\ZaloController@auth');    

    Route::get('/friends', 'SYS\ZaloController@getAllFriends');
    Route::get('/send.app.request/{FriendID}', 'SYS\ZaloController@sendAppRequest');

    Route::get('/app.friends', 'SYS\ZaloController@getFriendsUsedApp');

    Route::get('/app.sendMessage/{FriendID}/{Message}', 'SYS\ZaloController@sendMessage');

   
    
});

//Route::post('https://graph.zalo.me/v2.0/me/message', 'SYS\Social_ZaloController@index');

//PU Group
Route::group(['prefix' => 'pu'], function () {
    Route::get('/LoadPOList', 'PU\CaculatePOController@LoadPOList');
    Route::get('/LoadPODetail/{POID}', 'PU\CaculatePOController@LoadPODetail');
});

//FA Group
Route::group(['prefix' => 'fa'], function () {
    Route::get('/importData', 'Fa\PLReportDauThangController@LoadFileImportForFA')->name('importData');
    Route::post('/importData', 'Fa\PLReportDauThangController@importData')->name('importData');
    //Route::post('/fa.importData', 'FA\PLReportDauThangController@importShipment')->name('importData');

    Route::get('/PLReportAnnually', 'Fa\PLReportDauThangController@LoadPLReportAnnualyDefault')->name('fa.PLReportAnnually');
    Route::post('/PLReportAnnually', 'Fa\PLReportDauThangController@LoadPLReportAnnualy')->name('fa.PLReportAnnually');

    Route::get('/testXX', 'FA\PLReportDauThangController@PutDataDefault');

    Route::get('/plReport.monthly', 'Fa\PLReportDauThangController@LoadPLReportMonthlyDefault')->name('fa.plReport.monthly');
    Route::post('/plReport.monthly','Fa\PLReportDauThangController@LoadPLReportMonthly')->name('fa.plReport.monthly');
    // fa dau thang
    Route::get('/loadMKTBudget','Fa\PLReportDauThangController@LoadSEMAndPromotionBudget')->name('fa.loadMKTBudget');

    Route::get('/plReport.detail', 'Fa\PLReportDauThangController@LoadPLReportDetailNull')->name('fa.plReport.detail');
    Route::post('/plReport.detail','Fa\PLReportDauThangController@LoadPLReportDetail')->name('fa.plReport.detail');

    Route::get('/po.pending','Fa\PLReportDauThangController@LoadAvcPoDefault')->name('fa.avc.po');
    Route::post('/po.pending','Fa\PLReportDauThangController@LoadAvcPo')->name('fa.avc.po');

    Route::get('/CashFlow.ImportFile','Fa\CashFlowController@index');
    Route::post('/CashFlow.ImportFile','Fa\CashFlowController@ImportFile')->name('fa.CashFlow.ImportFile');

    Route::post('/ShowPODetail/{POID}', 'Fa\CashFlowController@ShowPODetail');
  
    Route::get('/ImportPuPlan','Fa\CashFlowController@ImportPuPlanDefault');
    Route::post('/ImportPuPlan','Fa\CashFlowController@ImportPuPlan')->name('fa.ImportPuPlan');

    Route::get('/Cashflow.Chart','Fa\CashFlowController@CashflowChartDefault')->name('fa.CashFlow.Chart');
    Route::post('/Cashflow.Chart','Fa\CashFlowController@CashflowChartNew')->name('fa.CashFlow.Chart');
});

//SAL Group
Route::group(['prefix' =>'Sales'], function () {
    
    Route::get('/product.infor.import','Sales\ImportSalesProductController@index')->name('sal.product.infor.import');
    Route::post('/product.infor.import','Sales\ImportSalesProductController@ImportProductSalesInfor')->name('sal.product.infor.import');

    Route::get('/selling.daily','Fa\CashFlowController@GetSellingDataDefault')->name('sal.selling.daily');
    Route::post('/selling.daily','Fa\CashFlowController@GetSellingData')->name('sal.selling.daily');

    Route::resource('/SalesProductInforController','Sales\SalesProductInforController');


    Route::get('/SalesProductInforController.LoadCostAndPriceOnAllChannel/{sku}','Sales\SalesProductInforController@LoadCostAndPriceOnAllChannel')->name('SalesProductInforController.LoadCostAndPriceOnAllChannel');

    Route::post('/SalesProductInforController.SaveNewChannelCostAndPrice','Sales\SalesProductInforController@SaveNewChannelCostAndPrice')->name('SalesProductInforController.SaveNewChannelCostAndPrice');

    Route::post('/SalesProductInforController.UpdateCostPrice','Sales\SalesProductInforController@UpdateCostPrice')->name('SalesProductInforController.UpdateCostPrice');


    //Route::get('/SalesProductInforController.Sales.Promotion.Management','Sales\PromotionController@index')->name('Sales.Promotion.Management');
   // Route::post('/SalesProductInforController.Sales.Promotion.Management','Sales\PromotionController@index')->name('Sales.Promotion.Management');


    Route::resource('/Promotion','Sales\PromotionController');
    Route::post('/promotion.destroy.detail/{DetailID}','Sales\PromotionController@destroyPromotionDetail')->name('promotion.destroy.detail');

    Route::get('/sal.wm.item_mng.import','Sales\SalesMNGController@index');
    Route::post('/sal.wm.item_mng.import','Sales\SalesMNGController@WMItemMNGImport')->name('sal.wm.item_mng.import');

    Route::get('/sal.wm.item_mng','Sales\SalesMNGController@LoadProductListSellingOnWMDSVDefault')->name('sal.wm.item_mng');
    Route::post('/sal.wm.item_mng','Sales\SalesMNGController@LoadProductListSellingOnWMDSV')->name('sal.wm.item_mng');

    Route::get('/sal.wm.actions','Sales\SalesMNGController@MakeSuggetActionOnWMDSVDefault')->name('sal.wm.actions');
    Route::post('/sal.wm.actions','Sales\SalesMNGController@MakeSuggetActionOnWMDSV')->name('sal.wm.actions');
});

//Prd Prd
Route::group(['prefix' => 'Prd'], function () {

    Route::resource('/Product','Prd\ProductController');
    Route::get('/Product/del/{id}','Prd\ProductController@destroy')->name('Product.del');
    Route::resource('/ProductLifeCircle','Prd\ProductLifeCircleController');
  

});

//Prd Group
Route::group(['prefix' => 'ProductNew'], function () {
    Route::get('/search','Prd\ProductControllerNew@autocompleteProduct');
    Route::POST('/check_asin/{asin}','Prd\ProductControllerNew@checkAsin');
    Route::POST('/select','Prd\ProductControllerNew@selectProduct');    
});

//Prd Inv
Route::group(['prefix' => 'Inv'], function () {
    Route::resource('/Transaction','Inv\TransactionControler');
});

//Prd Group
Route::group(['prefix' => 'ajax_pro'], function () {
    Route::get('/search','Inv\TransactionControler@autocompleteProduct');
    Route::POST('/check_sku/{sku}','Inv\TransactionControler@checkSku');
    Route::POST('/select','Inv\TransactionControler@selectProduct');
});

//ADMIN Group
Route::group(['prefix' => 'Admin'], function () {
    Route::get('/role/permission','Admin\RolePermissionController@LoadRolePermission')->name('admin.role.permission.load');
    Route::get('/role/permission/LoadFunction/{ModuleID}','Admin\RolePermissionController@LoadFunction')->name('admin.role.permission.function.load');
    Route::get('/role/permission/LoadMember/{RoleID}','Admin\RolePermissionController@LoadMember')->name('admin.role.permission.member.load');
    Route::get('/role/permission/LoadActionsInRolePermissions/{ModuleID}/{RolID}/{FncID}','Admin\RolePermissionController@LoadActionsInRolePermissions')->name('admin.role.permission.action.load');
    Route::get('/role/permission/update/{ID}/{Active}','Admin\RolePermissionController@UpdatePerMission')->name('admin.role.permission.update');
   
});


Route::group(['prefix' => 'Pu'], function () {
    Route::resource('/vendor','Pu\VendorController');

});


