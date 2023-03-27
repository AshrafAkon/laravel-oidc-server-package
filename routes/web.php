<?php

use Illuminate\Support\Facades\Route;

use AALP\Passport\Http\Controllers\AuthorizationController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/authorize', ['uses' => 'AuthorizationController@authorize'])->name('authorize');

    Route::get('/logout', [
        'uses' => '\AALP\Passport\SessionManagementController@logout',
    ])->name('logout');

    // Route::delete('/tokens/{token_id}', [
    //     'uses' => 'AuthorizedAccessTokenController@destroy',
    // ]);
});
Route::match(
    ['GET', 'POST'],
    '/userinfo',
    '\AALP\Passport\UserInfoController@userinfo'
)->name('userinfo');
Route::post('/token', [
    'uses' => 'AccessTokenController@issueToken',
    'middleware' => 'throttle',
])->name('token');




// $guard = config('passport.guard', null);

// Route::middleware(['web', $guard ? 'auth:' . $guard : 'auth'])->group(function () {
//     Route::post('/token/refresh', [
//         'uses' => 'TransientTokenController@refresh',
//         'as' => 'token.refresh',
//     ]);

//     Route::post('/authorize', [
//         'uses' => 'ApproveAuthorizationController@approve',
//         'as' => 'authorizations.approve',
//     ]);

//     Route::delete('/authorize', [
//         'uses' => 'DenyAuthorizationController@deny',
//         'as' => 'authorizations.deny',
//     ]);

//     Route::get('/tokens', [
//         'uses' => 'AuthorizedAccessTokenController@forUser',
//         'as' => 'tokens.index',
//     ]);

//     Route::delete('/tokens/{token_id}', [
//         'uses' => 'AuthorizedAccessTokenController@destroy',
//         'as' => 'tokens.destroy',
//     ]);

//     Route::get('/clients', [
//         'uses' => 'ClientController@forUser',
//         'as' => 'clients.index',
//     ]);

//     Route::post('/clients', [
//         'uses' => 'ClientController@store',
//         'as' => 'clients.store',
//     ]);

//     Route::put('/clients/{client_id}', [
//         'uses' => 'ClientController@update',
//         'as' => 'clients.update',
//     ]);

//     Route::delete('/clients/{client_id}', [
//         'uses' => 'ClientController@destroy',
//         'as' => 'clients.destroy',
//     ]);

//     Route::get('/scopes', [
//         'uses' => 'ScopeController@all',
//         'as' => 'scopes.index',
//     ]);

//     Route::get('/personal-access-tokens', [
//         'uses' => 'PersonalAccessTokenController@forUser',
//         'as' => 'personal.tokens.index',
//     ]);

//     Route::post('/personal-access-tokens', [
//         'uses' => 'PersonalAccessTokenController@store',
//         'as' => 'personal.tokens.store',
//     ]);

//     Route::delete('/personal-access-tokens/{token_id}', [
//         'uses' => 'PersonalAccessTokenController@destroy',
//         'as' => 'personal.tokens.destroy',
//     ]);
// });
