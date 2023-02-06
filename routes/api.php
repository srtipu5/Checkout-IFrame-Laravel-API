<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BkashController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Checkout (IFrame) User Part
Route::get('/bkash/create', [BkashController::class, 'createPayment'])->name('bkash-create');
Route::get('/bkash/execute', [BkashController::class, 'executePayment'])->name('bkash-execute');

// Checkout (IFrame) Refund Admin Part
Route::post('/bkash/refund', [BkashController::class, 'refundPayment'])->name('bkash-refund');
