<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
/*
Route::get('/', function () {
    return view('welcome');
});

Route::get('/info',function(){
    return phpinfo();
});
*/
Auth::routes();

//Route::get('/home', 'HomeController@index')->name('home');
Route::get('/', "\Sleefs\Controllers\Web\WebController@index");
Route::get('/pos', "\Sleefs\Controllers\Web\PosController@index");

//Route::get('/pos/{poid}', "\Sleefs\Controllers\Web\PosController@showPo");//Se permite el acceso público al detalle de las POs
Route::get('/pos/{poid}', "\Sleefs\Controllers\Web\ShowPosController@showPo");


Route::put('/products/updatepic', "\Sleefs\Controllers\Web\ProductsController@updateProductPic");

Route::get('/products/deleted', "\Sleefs\Controllers\Web\ProductsController@ShowRemoteDeletedProducts");
Route::post('/products/deleted', "\Sleefs\Controllers\Web\ProductsController@DeleteRemoteProducts");    

Route::post('/report', "\Sleefs\Controllers\Web\WebController@report");
Route::get('/inventoryreport',"\Sleefs\Controllers\Web\InventoryReportController@index");
Route::get('/inventoryreport/{irid}',"\Sleefs\Controllers\Web\InventoryReportController@showInventoryReport");
Route::post('/inventoryreport',"\Sleefs\Controllers\Web\InventoryReportController@createReport");



Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
