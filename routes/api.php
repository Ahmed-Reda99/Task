<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group( [ 'prefix' => 'auth' , 'middleware' => 'api' ] ,function(){
    
    Route::post('/login', 'AuthController@login');
    Route::post('/register', 'AuthController@register');
    Route::post('/logout', 'AuthController@logout');
    Route::post('/refresh', 'AuthController@refresh');
    Route::get('/user-profile', 'AuthController@userProfile');

    Route::post('/store-product', 'AuthController@storeProduct');
    Route::post('/update-product/{product:id}', 'AuthController@updateProduct');
    Route::delete('/delete-product/{product:id}', 'AuthController@deleteProduct');
    Route::get('/get-products', 'AuthController@getProducts');
    
});
