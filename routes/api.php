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

// Authentication Routes
Route::post('register', 'Api\AuthController@register')->name('register');
Route::post('login', 'Api\AuthController@login')->name('login');

// Public Event Routes
Route::get('events', 'Api\EventController@index');
Route::get('events/{id}', 'Api\EventController@show');


// Protected Routes
Route::post('events', 'Api\EventController@store');
Route::put('events/{id}', 'Api\EventController@update');
Route::delete('events/{id}', 'Api\EventController@destroy');

Route::middleware('auth:api')->group(function () {
    // Auth Routes
    Route::post('logout', 'Api\AuthController@logout')->name('logout');
    Route::get('me', 'Api\AuthController@me')->name('me');

    // Event Routes (Organizer & Admin)
    Route::middleware('role:organizer,admin')->group(function () {
        // Route::post('events', 'Api\EventController@store'); // moved out for testing
        // Route::put('events/{id}', 'Api\EventController@update'); 
        // Route::delete('events/{id}', 'Api\EventController@destroy');

        // Ticket Routes (Organizer & Admin)
        Route::post('events/{event_id}/tickets', 'Api\TicketController@store');
        Route::put('tickets/{id}', 'Api\TicketController@update');
        Route::delete('tickets/{id}', 'Api\TicketController@destroy');
    });

    // Booking Routes (Customer)
    Route::middleware('role:customer')->group(function () {
        Route::post('tickets/{id}/bookings', 'Api\BookingController@store')
            ->middleware('prevent.double.booking');
        Route::get('bookings', 'Api\BookingController@index');
        Route::put('bookings/{id}/cancel', 'Api\BookingController@cancel');
    });

    // Payment Routes
    Route::post('bookings/{id}/payment', 'Api\PaymentController@store');
    Route::get('payments/{id}', 'Api\PaymentController@show');
});
