<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;

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

    // User Register 
    Route::post('/register', [AuthController::class, 'register']);

    // User Login
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::group(['middleware' => ['auth:sanctum']], function () {

        // Get users
        Route::get('/users', [AuthController::class, 'index']);

        // Logout
        Route::post('/logout', [AuthController::class, 'logout']);

        // Adding News
        Route::get('/add-news', [ArticleController::class, 'addNews']);

        // Get articles
        Route::get('/articles', [ArticleController::class, 'index']);

        // Feed
        Route::get('/feed', [ArticleController::class, 'feed']);

        // Get Fields for Articles
        Route::get('/get_fields', [ArticleController::class, 'getFields']);

        // Get Settings
        Route::get('/get_setting', [ArticleController::class, 'getSetting']);
        
        // Add Setting
        Route::post('/add_Setting', [ArticleController::class, 'addSetting']);

        // Delete Setting
        Route::post('/delete_setting', [ArticleController::class, 'deleteSetting']);
    });
