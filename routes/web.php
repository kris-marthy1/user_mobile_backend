<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ServiceWindowController;


Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [LoginController::class, 'Login'])->name('login');
Route::post('/validate-google-user', [LoginController::class, 'validateGoogleUser']);
Route::get('/users/{school_id}', [LoginController::class, 'getUserDetails']);


Route::get('/get-tables', [ServiceWindowController::class, 'getFilteredTables']);
Route::post('/get-table-data', [ServiceWindowController::class, 'getTableData']);
Route::post('/get-table-columns', [ServiceWindowController::class, 'getTableColumns']);

Route::post('/check-user-in-queue', [ServiceWindowController::class, 'checkUserInQueue']);
Route::post('/join-queue', [ServiceWindowController::class, 'joinQueue']);
Route::post('/exit-queue', [ServiceWindowController::class, 'exitQueue']);
Route::post('/queue-status', [ServiceWindowController::class, 'queueStatus']);
Route::post('/duration', [ServiceWindowController::class, 'duration']);
// Route::post('/queue-session', [ServiceWindowController::class, 'queueSession']);
Route::get('/fetch-ip', [ServiceWindowController::class, 'fetchIP']);


