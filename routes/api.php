<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
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


Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::get('/getCurrentUserId', [UserController::class, 'getCurrentUserId']);
});

    //Route::get('/allUserFiles', [UserController::class, 'allUserFiles']);


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/createGroup',[GroupController::class,'createGroup'])->middleware('CheckGroupName');
    Route::delete('/deleteGroup',[GroupController::class,'deleteGroup'])->middleware('FileReserved');
    Route::get('/allGroupsForOwnerUser', [GroupController::class, 'allGroupsForUser']);
    Route::get('/allGroupsForMemberUser', [GroupController::class, 'allGroupsForMemberUser']);
    Route::get('/allGroupFiles',[GroupController::class,'allGroupFiles']);
    Route::post('/RequestToJoinGroup',[GroupController::class,'RequestToJoinGroup']);
    Route::post('/AcceptedRequest',[GroupController::class,'AcceptedRequest']);
    Route::post('/refuseRequest',[GroupController::class,'refuseRequest']);
    Route::post('/allSentRequestsFromGroupAdmin',[GroupController::class,'allSentRequestsFromGroupAdmin']);
    Route::get('/groupUsers',[GroupController::class,'groupUsers']);
    Route::get('/displayAllUser',[GroupController::class,'displayAllUser']);
    Route::get('/displayAllGroups',[GroupController::class,'displayAllGroups']);
    Route::get('/searchUser',[GroupController::class,'searchUser']);

});
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('event:clear');

    return "Cache cleared successfully!";
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/uploadFileToGroup',[FileController::class,'uploadFileToGroup']);
    Route::post('/downloadFile',[FileController::class,'downloadFile'])->middleware(['CheckMember','FileReserved']);
    Route::delete('/deleteFile',[FileController::class,'deleteFile'])->middleware(['CheckOwner','FileReserved']);
    Route::post('/checkIn',[FileController::class,'checkIn'])->middleware(['CheckMember','FileReserved']);
    // TODO : CHECK IN MULTIPLE FILES
    Route::post('/checkOut',[FileController::class,'checkOut']);

});
