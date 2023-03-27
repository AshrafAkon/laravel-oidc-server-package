<?php

namespace AALP\Passport;

use Laravel\Passport\RouteRegistrar as LaravelRouteRegistrar;

// class RouteRegistrar
// {

//     public function all()
//     {
//         // parent::all();

//         $this->forUserinfo();
//         $this->forIntrospect();
//     }

//     public function forAuthorization()
//     {
//         $this->router->group(['middleware' => ['web', 'auth:sanctum']], function ($router) {
//             $router->get('/authorize', [
//                 'uses' => '\AALP\Passport\Http\Controllers\AuthorizationController@authorize',
//             ])->name('oauth.authorize');


//             $router->get('/logout', [
//                 'uses' => '\AALP\Passport\SessionManagementController@logout',
//             ])->name('oidc.logout');
//         });
//     }

//     public function forUserinfo()
//     {
//         $this->router->group([], function ($router) {
//             $this->router->match(['get', 'post'], '/userinfo', [
//                 'uses' => '\AALP\Passport\UserInfoController@userinfo',
//             ])->name('oidc.userinfo');
//         });
//     }

//     public function forIntrospect()
//     {
//         $this->router->group([], function ($router) {
//             $this->router->post('/introspect', [
//                 'uses' => '\AALP\Passport\IntrospectionController@introspect',
//             ])->name('oauth.introspect');

//             $this->router->post('/revoke', [
//                 'uses' => '\AALP\Passport\RevokeController@index',
//             ])->name('oauth.revoke');
//         });
//     }

//     public function forWellKnown()
//     {
//         $this->router->group([], function ($router) {
//             $router->get('/.well-known/openid-configuration', [
//                 'uses' => '\AALP\Passport\ProviderController@wellknown',
//             ])->name('oidc.configuration');

//             $router->get('/.well-known/jwks.json', [
//                 'uses' => '\AALP\Passport\ProviderController@jwks',
//             ])->name('oidc.jwks');

//             $router->get('/.well-known/webfinger', [
//                 'uses' => '\AALP\Passport\ProviderController@webfinger',
//             ])->name('oidc.webfinger');
//         });
//     }

//     public function forManagement()
//     {
//         $this->router->group(['middleware' => ['api']], function ($router) {
//             $router->get('/oidc/provider', [
//                 'uses' => '\AALP\Passport\ProviderController@index',
//             ]);

//             $router->put('/oidc/provider', [
//                 'uses' => '\AALP\Passport\ProviderController@update',
//             ]);
//         });
//     }

//     public function forOIDCClients()
//     {
//         $this->router->group(['middleware' => ['api']], function ($router) {
//             $router->get('/connect/register', [
//                 'uses' => '\AALP\Passport\ClientController@forUser',
//             ])->name('oidc.manage.client.list');

//             $router->post('/connect/register', [
//                 'uses' => '\AALP\Passport\ClientController@store',
//             ])->name('oidc.manage.client.create');

//             // Not in the specs, yet useful
//             $router->get('/connect/register/{client_id}', [
//                 'uses' => '\AALP\Passport\ClientController@get',
//             ])->name('oidc.manage.client.get');

//             $router->put('/connect/register/{client_id}', [
//                 'uses' => '\AALP\Passport\ClientController@update',
//             ])->name('oidc.manage.client.replace');

//             $router->delete('/connect/register/{client_id}', [
//                 'uses' => '\AALP\Passport\ClientController@destroy',
//             ])->name('oidc.manage.client.delete');
//         });
//     }

//     /**
//      * Register the routes for retrieving and issuing access tokens.
//      *
//      * @return void
//      */
//     public function forAccessTokens()
//     {
//         $this->router->post('/token', [
//             'uses' => '\AALP\Passport\Http\Controllers\AccessTokenController@issueToken',
//             'middleware' => 'throttle',
//         ])->name('oauth.token');

//         $this->router->group(['middleware' => ['web', 'auth']], function ($router) {
//             $router->delete('/tokens/{token_id}', [
//                 'uses' => 'AuthorizedAccessTokenController@destroy',
//             ]);
//         });
//     }
// }
