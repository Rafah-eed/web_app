<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|uth
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});



Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});

Route::controller(FileController::class)->group(function(){
    Route::post('store', 'store');
});

Route::post('/createGroup',[GroupController::class,'createGroup'])->middleware('CheckGroupName');
Route::delete('/deleteGroup',[GroupController::class,'deleteGroup'])->middleware('FileReserved');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/allGroupsForOwnerUser', [GroupController::class, 'allGroupsForUser']);
    Route::get('/allGroupsForMemberUser', [GroupController::class, 'allGroupsForMemberUser']);
    Route::get('/allUserFiles', [UserController::class, 'allUserFiles']);
});
