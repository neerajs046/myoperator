<?php

use Illuminate\Http\Request;

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
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
//Auth routes
Route::post('login', 'AuthController@login');
Route::post('register', 'AuthController@register');
// Route for admin permissions
Route::prefix('admin')->group(function() {
	Route::post('login', 'AuthController@adminLogin');
	Route::post('register', 'AuthController@adminRegister');
});
Route::post('/bidding', 'BiddingController@createItem')->middleware(['auth:api', 'scope:create-item']);
Route::get('/bidding/{item_id}', 'BiddingController@getItem')->middleware(['auth:api', 'scope:get-item']);
Route::get('/bidding/{item_id}/time', 'BiddingController@getItemBiddingTime')->middleware(['auth:api', 'scope:get-item-time']);
Route::delete('api/bidding/{item_id}', 'BiddingController@markBiddingComplete')->middleware(['auth:api', 'scope:delete-item']);
Route::post('/place-bid', 'BiddingController@placeBid')->middleware(['auth:api', 'scope:place-bid']);

Route::get('/get-final-price', 'BiddingController@getFinalBidPrice');