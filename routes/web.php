<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Auth::routes();

Route::get('/', 'DisplayController@index');

Route::get('/api', 'APIController@index');

Route::post('/tableGet', 'DisplayController@addOn');

Route::get('/account/{id}', 'DisplayController@accountList');

Route::get('/date/{date}', 'DisplayController@dates');
