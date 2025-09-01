<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\packagesController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\withdrawController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::any('/api-handle-package-transaction-9pay', [packagesController::class, 'apiHandlePackageTransaction9Pay'])->name('apiHandlePackageTransaction9Pay');

Route::any('/api-handle-package-transaction', [packagesController::class, 'handlePackageTransaction'])->name('handlePackageTransaction');

Route::any('/api-handle-withdraw-transaction', [withdrawController::class, 'handleWithdrawTransaction'])->name('handleWithdrawTransaction');

Route::any('active-trades', [loginController::class, 'activeTrades'])->name('activeTrades');

Route::any('toast-details', [loginController::class, 'toastDetails'])->name('toastDetails');
