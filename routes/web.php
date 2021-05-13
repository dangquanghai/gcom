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

// system
Route::get('/home', 'HomeController@index')->name('home');
Route::get('/testhome', 'HomeController@test');

Route::get('/home/categories', 'CategoriesController@index')->name('list_category');

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('/resetPass', function () {
   return view('auth.passwords.reset');
});

// PU
//Route::get('/pu.CreatePO','PU\CaculatePOController@CreateAllPOEstimate');
Route::get('/pu.LoadPOList', 'PU\CaculatePOController@LoadPOList');
Route::get('/pu.LoadPODetail/{POID}', 'PU\CaculatePOController@LoadPODetail');

//Route::get('/pu.ShowPODetail/{POID}', 'PU\CaculatePOController@ShowPODetail');
//Route::get('/pu.ShowUseContainer', 'PU\CaculatePOController@ShowUseContainer');

// FA

//Route::get('/fa.test', 'FA\CashFlowController@GetSellingData');
/*
Route::get('/fa.importData', 'FA\PLReportController@LoadFileImportForFA');
Route::post('/fa.importData', 'FA\PLReportController@importData')->name('importData');

Route::get('/fa.summary', 'FA\PLReportController@LoadFASummaryNull');
Route::post('/fa.summary', 'FA\PLReportController@LoadFASummary')->name('LoadFASummary');

Route::get('/fa.showPLReport', 'FA\PLReportController@index');
Route::post('/fa.showPLReport', 'FA\PLReportController@showPLReport')->name('showPLReport');
*/
//Route::get('/fa.PLReport', 'FA\PLReportDauThangController@index');
//Route::post('/fa.PLReport', 'FA\PLReportDauThangController@LoadPLReport')->name('fa.PLReport');
// IMport data PL report
Route::get('/fa.importData', 'Fa\PLReportDauThangController@LoadFileImportForFA')->name('importData');
Route::post('/fa.importData', 'Fa\PLReportDauThangController@importData')->name('importData');
//Route::post('/fa.importData', 'FA\PLReportDauThangController@importShipment')->name('importData');

Route::get('/fa.PLReportAnnually', 'Fa\PLReportDauThangController@LoadPLReportAnnualyDefault')->name('fa.PLReportAnnually');
Route::post('/fa.PLReportAnnually', 'Fa\PLReportDauThangController@LoadPLReportAnnualy')->name('fa.PLReportAnnually');

Route::get('/fa.testXX', 'FA\PLReportDauThangController@PutDataDefault');

Route::get('/fa.plReport.monthly', 'Fa\PLReportDauThangController@LoadPLReportMonthlyDefault')->name('fa.plReport.monthly');
Route::post('/fa.plReport.monthly','Fa\PLReportDauThangController@LoadPLReportMonthly')->name('fa.plReport.monthly');
// fa dau thang
Route::get('/fa.loadMKTBudget','Fa\PLReportDauThangController@LoadSEMAndPromotionBudget')->name('fa.loadMKTBudget');

Route::get('/fa.plReport.detail', 'Fa\PLReportDauThangController@LoadPLReportDetailNull')->name('fa.plReport.detail');
Route::post('/fa.plReport.detail','Fa\PLReportDauThangController@LoadPLReportDetail')->name('fa.plReport.detail');

Route::get('/fa.po.pending','Fa\PLReportDauThangController@LoadAvcPoDefault')->name('fa.avc.po');
Route::post('/fa.po.pending','Fa\PLReportDauThangController@LoadAvcPo')->name('fa.avc.po');

Route::get('/fa.testxx','Fa\PLReportDauThangController@testxx');


// FA-> Cashflow
//Route::get('/fa.test','FA\PLReportDauThangController@UpdateBasicSellingInforOnSalesChannel');

Route::get('/fa.CashFlow.ImportFile','Fa\CashFlowController@index');
Route::post('/fa.CashFlow.ImportFile','Fa\CashFlowController@ImportFile')->name('fa.CashFlow.ImportFile');

Route::post('/fa.ShowPODetail/{POID}', 'Fa\CashFlowController@ShowPODetail');

Route::get('/fa.test','Fa\CashFlowController@Test');
Route::get('/fa.ImportPuPlan','Fa\CashFlowController@ImportPuPlanDefault');
Route::post('/fa.ImportPuPlan','Fa\CashFlowController@ImportPuPlan')->name('fa.ImportPuPlan');

Route::get('/fa.Cashflow.Chart','Fa\CashFlowController@CashflowChartDefault')->name('fa.CashFlow.Chart');
Route::post('/fa.Cashflow.Chart','Fa\CashFlowController@CashflowChartNew')->name('fa.CashFlow.Chart');

Route::get('/sal.selling.daily','Fa\CashFlowController@GetSellingDataDefault')->name('sal.selling.daily');
Route::post('/sal.selling.daily','Fa\CashFlowController@GetSellingData')->name('sal.selling.daily');

//Route::get('/sal.PromotionAndSemBudget','FA\CashFlowController@LoadSEMAndPromotionBudgetDefault');
//Route::post('/fa.loadMKTBudget','FA\CashFlowController@LoadSEMAndPromotionBudget')->name('fa.loadMKTBudget');
// PRODUCT
//Route::get('/prd.product.list','PRD\ProductController@index')->name('prd.product.list');
//Route::post('/prd.product.list','PRD\ProductController@GetProductList')->name('prd.product.list');

Route::resource('/Product','Prd\ProductController');
Route::get('/Product/del/{id}','Prd\ProductController@destroy')->name('Product.del');
Route::resource('/ProductLifeCircle','Prd\ProductLifeCircleController');

Route::resource('/Transaction','Inv\TransactionControler');

Route::get('ajax_pro/search','Inv\TransactionControler@autocompleteProduct');
Route::POST('ajax_pro/check_sku/{sku}','Inv\TransactionControler@checkSku');
Route::POST('ajax_pro/select','Inv\TransactionControler@selectProduct');

Route::resource('/SalesProductInforController','Sales\SalesProductInforController');

Route::get('/sal.import.sales.product.infor','Sales\SalesProductInforController@LoadFileProductSalesInfor')->name('sal.import.sales.product.infor');
Route::post('/sal.import.sales.product.infor','Sales\SalesProductInforController@ImportProductSalesInfor')->name('sal.import.sales.product.infor');

Route::get('/SalesProductInforController.LoadCostAndPriceOnAllChannel/{sku}','Sales\SalesProductInforController@LoadCostAndPriceOnAllChannel')->name('SalesProductInforController.LoadCostAndPriceOnAllChannel');

Route::post('/SalesProductInforController.UpdateCostPrice','Sales\SalesProductInforController@UpdateCostPrice')->name('SalesProductInforController.UpdateCostPrice');

Route::get('/SalesProductInforController.Sales.Promotion.Management','Sales\SalesProductInforController@LoadPromotionsDefault')->name('Sales.Promotion.Management');
Route::post('/SalesProductInforController.Sales.Promotion.Management','Sales\SalesProductInforController@LoadPromotions')->name('Sales.Promotion.Management');

//Route::post('/SalesProductInforController.UpdateCostPrice','Sales\SalesProductInforController@UpdateCostPrice')->name('SalesProductInforController.UpdateCostPrice');

//Route::get('/sal.product.infor','Sales\SalesProductInforController@LoadSalesProductListDefault')->name('sal.product.infor');
//Route::post('/sal.product.infor','Sales\SalesProductInforController@LoadSalesProductList')->name('sal.product.infor');

//Route::get('/sal.product.infor.detail/{id}','Sales\SalesProductInforController@LoadSalesProductInforDetail')->name('sal.product.infor.detail');
//Route::post('/sal.product.infor.detail/update/{id}','Sales\SalesProductInforController@LoadSalesProductInforDetail')->name('sal.product.infor.detail/update');

//Route::resource('/SalesProductInforController','Sales\SalesProductInforController');
//Route::resource('/sal.product.infor','Sales\SalesProductInforController');
//Route::get('/sal.product.infor','Sales\SalesProductInforController@LoadProductListDefault')->name('sal.product.infor');
//Route::post('/sal.product.infor','Sales\SalesProductInforController@LoadProductList')->name('sal.product.infor');

Route::get('/sal.wm.item_mng.import','Sales\SalesMNGController@index');
Route::post('/sal.wm.item_mng.import','Sales\SalesMNGController@WMItemMNGImport')->name('sal.wm.item_mng.import');

Route::get('/sal.wm.item_mng','Sales\SalesMNGController@LoadProductListSellingOnWMDSVDefault')->name('sal.wm.item_mng');
Route::post('/sal.wm.item_mng','Sales\SalesMNGController@LoadProductListSellingOnWMDSV')->name('sal.wm.item_mng');

//Route::get('/sal.wm.item_mng','Sales\SalesMNGController@LoadProductListSellingOnWMDSVDefault')->name('sal.wm.item_mng');

Route::get('/sal.wm.actions','Sales\SalesMNGController@MakeSuggetActionOnWMDSVDefault')->name('sal.wm.actions');
Route::post('/sal.wm.actions','Sales\SalesMNGController@MakeSuggetActionOnWMDSV')->name('sal.wm.actions');







