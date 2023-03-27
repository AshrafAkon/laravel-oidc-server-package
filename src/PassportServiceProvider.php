<?php

namespace AALP\Passport;

use DateInterval;
use AALP\OpenID\Grant\AuthCodeGrant;
use AALP\OpenID\Grant\ImplicitGrant;
use AALP\OpenID\Repositories\ClaimRepositoryInterface;
use AALP\OpenID\Repositories\UserRepositoryInterface;
use AALP\OpenID\ResponseTypes\BearerTokenResponse;
use AALP\OpenID\Session;
use AALP\Passport\Bridge\AccessTokenRepository;
use AALP\OpenID\Repositories\AccessTokenRepositoryInterface;
use AALP\Passport\Bridge\ClaimRepository;
use AALP\Passport\Bridge\UserRepository;
use AALP\Passport\Guards\TokenGuard;
use AALP\Passport\Model\Client;
use AALP\Passport\Model\PersonalAccessClient;
use Laravel\Passport\Bridge\AccessTokenRepository as BridgeAccessTokenRepository;
use Laravel\Passport\Bridge\AuthCodeRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\ClientRepository as PassportClientRepository;
use Laravel\Passport\PassportServiceProvider as LaravelPassportServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\PassportUserProvider;
use Laravel\Passport\TokenRepository;
use Illuminate\Support\Facades\Route;
use AALP\Passport\Http\Controllers\AuthorizationController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Auth\StatefulGuard;

class PassportServiceProvider extends LaravelPassportServiceProvider
{

    protected function getClientModel()
    {
        return Client::class;
    }

    protected function getPersonalAccessClientModel()
    {
        return PersonalAccessClient::class;
    }

    public function boot()
    {
        Passport::useClientModel($this->getClientModel());
        Passport::usePersonalAccessClientModel($this->getPersonalAccessClientModel());
        // Passport::useTokenModel()

        parent::boot();

        $this->app->bindIf(ClaimRepositoryInterface::class, ClaimRepository::class);
        $this->app->bindIf(UserRepositoryInterface::class, UserRepository::class);

        $this->app->singleton(AccessTokenRepositoryInterface::class, function ($app) {
            return $this->app->make(AccessTokenRepository::class);
        });
        $this->app->singleton(BridgeAccessTokenRepository::class, function ($app) {
            return $app->make(AccessTokenRepositoryInterface::class);
        });
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__ . '/../config/passport.php', 'passport');

        Passport::setClientUuids($this->app->make(Config::class)->get('passport.client_uuids', false));

        $this->app->when(AuthorizationController::class)
            ->needs(StatefulGuard::class)
            ->give(fn () => Auth::guard(config('passport.guard', null)));

        $this->registerAuthorizationServer();
        $this->registerClientRepository();
        $this->registerJWTParser();
        $this->registerResourceServer();
        $this->registerGuard();

        Passport::authorizationView('passport::authorize');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../examples/App/Models/User.php' => app_path('Models/User.php'),
                    __DIR__ . '/../examples/App/Providers/AuthServiceProvider.php' =>
                    app_path('Providers/AuthServiceProvider.php'),
                    __DIR__ . '/../examples/config/auth.php' =>
                    config_path('auth.php'),
                ]
            );
        }
    }
    /**
     * Register the Passport routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (Passport::$registersRoutes) {
            Route::group([
                'as' => 'oidc.',
                'prefix' => config('passport.path', 'oidc'),
                'namespace' => '\AALP\Passport\Http\Controllers',
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }
    }
    protected function makeCryptKey($type)
    {
        if ($type == 'private') {
            return resolve(KeyRepository::class)->getPrivateKey();
        } else {
            return resolve(KeyRepository::class)->getPublicKey();
        }
    }
    /**
     * Register the resource server.
     *
     * @return void
     */
    protected function registerResourceServer()
    {
        $this->app->singleton(ResourceServer::class, function () {
            // TODO: consider using AdvancedResourceServer
            return new ResourceServer(
                $this->app->make(Bridge\AccessTokenRepository::class),
                $this->makeCryptKey('public')
            );
        });
    }

    public function makeAuthorizationServer()
    {
        return  new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(ScopeRepository::class),
            resolve(KeyRepository::class)->getPrivateKey(),
            app('encrypter')->getKey(),
            new BearerTokenResponse
        );
    }

    /**
     * Register the client repository.
     *
     * @return void
     */
    protected function registerClientRepository()
    {
        $this->app->singleton(PassportClientRepository::class, function ($container) {
            $config = $container->make('config')->get('passport.personal_access_client');

            return new ClientRepository($config['id'] ?? null, $config['secret'] ?? null);
        });
    }



    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                new PassportUserProvider(Auth::createUserProvider($config['provider']), 'users'),
                $this->app->make(TokenRepository::class),
                $this->app->make(ClientRepository::class),
                $this->app->make('encrypter'),
                $request
            ))->user($request);
        }, $this->app['request']);
    }


    // /**
    //  * Bootstrap the application services.
    //  *
    //  * @return void
    //  */
    // public function boot()
    // {
    //     $this->registerRoutes();
    //     $this->registerResources();
    //     $this->registerMigrations();
    //     $this->registerPublishing();
    //     $this->registerCommands();

    //     $this->deleteCookieOnLogout();
    // }


    /**
     * Register the Passport resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'passport');
    }

    /**
     * Register the Passport migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        # && !config('passport.client_uuids')
        if ($this->app->runningInConsole() && Passport::$runsMigrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    // /**
    //  * Register the package's publishable resources.
    //  *
    //  * @return void
    //  */
    // protected function registerPublishing()
    // {
    //     if ($this->app->runningInConsole()) {
    //         $this->publishes([
    //             __DIR__ . '/../database/migrations' => database_path('migrations'),
    //         ], 'passport-migrations');

    //         $this->publishes([
    //             __DIR__ . '/../resources/views' => base_path('resources/views/vendor/passport'),
    //         ], 'passport-views');

    //         $this->publishes([
    //             __DIR__ . '/../config/passport.php' => config_path('passport.php'),
    //         ], 'passport-config');
    //     }
    // }

    /**
     * Register the Passport Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         Console\InstallCommand::class,
        //         Console\ClientCommand::class,
        //         Console\HashCommand::class,
        //         Console\KeysCommand::class,
        //         Console\PurgeCommand::class,
        //     ]);
        // }
    }


    /**
     * Register the authorization server.
     *
     * @return void
     */
    protected function registerAuthorizationServer()
    {
        $this->app->singleton(AuthorizationServer::class, function () {
            return tap($this->makeAuthorizationServer(), function ($server) {
                $server->setDefaultScope(Passport::$defaultScope);

                $server->enableGrantType(
                    $this->makeAuthCodeGrant(),
                    Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makeRefreshTokenGrant(),
                    Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makePasswordGrant(),
                    Passport::tokensExpireIn()
                );

                // $server->enableGrantType(
                //     new PersonalAccessGrant,
                //     Passport::personalAccessTokensExpireIn()
                // );

                // $server->enableGrantType(
                //     new ClientCredentialsGrant,
                //     Passport::tokensExpireIn()
                // );

                if (Passport::$implicitGrantEnabled) {
                    $server->enableGrantType(
                        $this->makeImplicitGrant(),
                        Passport::tokensExpireIn()
                    );
                }
            });
        });
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     *
     * @return \League\OAuth2\Server\Grant\AuthCodeGrant
     */
    protected function makeAuthCodeGrant()
    {
        return tap($this->buildAuthCodeGrant(), function ($grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     *
     * @return \League\OAuth2\Server\Grant\AuthCodeGrant
     */
    protected function buildAuthCodeGrant()
    {
        $authCodeGrant =  new AuthCodeGrant(
            $this->app->make(AuthCodeRepository::class),
            $this->app->make(RefreshTokenRepository::class),
            $this->app->make(ClaimRepositoryInterface::class),
            $this->app->make(Session::class),
            new DateInterval('PT10M'),
            new DateInterval('PT10M')
        );
        $authCodeGrant->setIssuer(url('/'));
        return $authCodeGrant;
    }





    /**
     * Create and configure an instance of the Implicit grant.
     *
     * @return \League\OAuth2\Server\Grant\ImplicitGrant
     */
    protected function makeImplicitGrant()
    {
        return new ImplicitGrant(
            $this->app->make(UserRepositoryInterface::class),
            $this->app->make(ClaimRepositoryInterface::class),
            new DateInterval('PT10M'),
            new DateInterval('PT10M')
        );
    }

    /**
     * Register the token guard.
     *
     * @return void
     */
    protected function registerGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('passport', function ($app, $name, array $config) {
                return tap($this->makeGuard($config), function ($guard) {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }



    /**
     * Register the cookie deletion event handler.
     *
     * @return void
     */
    protected function deleteCookieOnLogout()
    {
        // Event::listen(Logout::class, function () {
        //     if (Request::hasCookie(Passport::cookie())) {
        //         Cookie::queue(Cookie::forget(Passport::cookie()));
        //     }
        // });
    }
}
