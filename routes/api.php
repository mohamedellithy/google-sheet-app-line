<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingSheetController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('booking-sheet',[BookingSheetController::class,'booking_sheet']);
Route::get('sheet-service/{user_id}',[BookingSheetController::class,'booking_sheet']);
Route::post('/auto-replay/{user_id}/{instance_id}/{access_token}',[BookingSheetController::class,'auto_replay']);
Route::get('/auto-replay/{user_id}/{instance_id}/{access_token}',[BookingSheetController::class,'auto_replay']);
