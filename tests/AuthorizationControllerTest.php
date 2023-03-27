<?php

namespace AALPPassportTests;

use DateInterval;
use AALP\OpenID\Grant\AuthCodeGrant;
use AALP\OpenID\RequestTypes\AuthenticationRequest;
use AALP\OpenID\ResponseTypes\BearerTokenResponse;
use AALP\OpenID\Session;
use AALP\Passport\Bridge\ClaimRepository;
use AALP\Passport\Bridge\ClientRepository as AALPClientRepository;
use AALP\Passport\Model\Client;
use AALP\Passport\ClientRepository;
use AALP\Passport\Http\Controllers\AuthorizationController;
use AALP\Passport\KeyRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AuthCode;
use Laravel\Passport\Bridge\AuthCodeRepository;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Mockery as m;
use Laravel\Passport\Tests\Feature\PassportTestCase;

use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface as LeagueAccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class AuthorizationControllerTest extends PassportTestCase
{
    public static function setUpBeforeClass(): void
    {
        chmod(__DIR__ . '/files/oauth-private.key', 0600);
        chmod(__DIR__ . '/files/oauth-public.key', 0600);
    }

    protected function setUp(): void
    {
        Passport::loadKeysFrom(__DIR__ . '/files');
        parent::setUp();

        chmod(__DIR__ . '/../vendor/laravel/passport/tests/Feature/../keys/oauth-private.key', 0660);
        chmod(__DIR__ . '/../vendor/laravel/passport/tests/Feature/../keys/oauth-public.key', 0660);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testAuthorizationBasic()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->andReturn(
            $client = new Client([])
        );

        $client  = m::mock(ClientEntityInterface::class);
        $client->shouldReceive('getRedirectUri')->andReturn('https://test123.nl');
        $client->shouldReceive('isConfidential')->andReturn(false);
        $client->shouldReceive('getIdentifier')->andReturn('123');

        $clientRepository = m::mock(AALPClientRepository::class);
        $clientRepository->shouldReceive('getClientEntity')->andReturn($client);

        $scopeEntity = new Scope('openid');

        $scopeRepository = m::mock(ScopeRepositoryInterface::class);
        $scopeRepository->shouldReceive('getScopeEntityByIdentifier')->andReturn(
            $scopeEntity
        );
        $scopeRepository->shouldReceive('finalizeScopes')->andReturn(
            [$scopeEntity]
        );

        $tokenRepository = m::mock(LeagueAccessTokenRepositoryInterface::class);
        $tokenRepository->shouldReceive('getNewToken')->andReturn(
            new AccessToken('test', [$scopeEntity], $client)
        );
        $tokenRepository->shouldReceive('persistNewAccessToken')->andReturn(true);

        $server = new AuthorizationServer(
            $clientRepository,
            $tokenRepository,
            $scopeRepository,
            (new KeyRepository())->getPrivateKey(),
            "test",
            new BearerTokenResponse
        );

        $claimRepository = m::mock(ClaimRepository::class);
        $claimRepository->shouldReceive('claimsRequestToEntities')->andReturn([]);

        $authCodeRepository = m::mock(AuthCodeRepository::class);
        $authCodeRepository->shouldReceive('getNewAuthCode')->andReturn(new AuthCode());
        $authCodeRepository->shouldReceive('persistNewAuthCode')->andReturn(true);
        $authCodeRepository->shouldReceive('isAuthCodeRevoked')->andReturn(false);
        $authCodeRepository->shouldReceive('revokeAuthCode')->andReturn(true);

        $refreshTokenRepository = m::mock(RefreshTokenRepository::class);
        $refreshTokenRepository->shouldReceive('getNewRefreshToken')->andReturn(new RefreshToken());
        $refreshTokenRepository->shouldReceive('persistNewRefreshToken')->andReturn(true);

        $grant = new AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            $claimRepository,
            new Session,
            new DateInterval('P1Y'),
            new DateInterval('P1Y')
        );

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));
        $grant->setIssuer('https://issuer.example.com');
        $grant->disableRequireCodeChallengeForPublicClients();

        $server->enableGrantType(
            $grant,
            new DateInterval('P1Y')
        );

        $response = m::mock(ResponseFactory::class);

        $controller = new AuthorizationController(
            $server,
            $response
        );

        $authenticationRequest = m::mock(AuthenticationRequest::class);

        $client = m::mock(ClientEntityInterface::class);
        $client->shouldReceive('getIdentifier')->andReturn('test');
        $client->shouldReceive('isConfidential')->andReturn(true);

        $authenticationRequest->shouldReceive('getClient')->andReturn(
            $client
        );

        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getKey')->andReturn('test');
        $user->shouldReceive('getAuthIdentifier')->andReturn('test');

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user);

        $tokens = m::mock(TokenRepository::class);
        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->andReturn([]);
        $tokens->shouldReceive('findValidToken')->andReturn(
            $token
        );

        $serverRequest = m::mock(ServerRequestInterface::class);
        $serverRequest->shouldReceive('getQueryParams')->andReturn([
            'response_type' => 'code',
            'client_id' => '123',
            'scope' => 'openid',
            'redirect_uri' => 'https://test123.nl'
        ]);
        $serverRequest->shouldReceive('getServerParams')->andReturn([]);
        $serverRequest->shouldReceive('hasHeader')->andReturn(false);

        /**  */
        $result = $controller->authorize(
            $serverRequest,
            $request,
            $clients,
            $tokens
        );

        $location = $result->headers->get('Location');

        $this->assertNotNull($location);
        $parsed = parse_url($location);

        $this->assertArrayHasKey('query', $parsed);
        parse_str($parsed['query'], $parseStr);
        $this->assertArrayHasKey('code', $parseStr);

        $controller = new AccessTokenController($server, $tokens, new Parser(new JoseEncoder()));

        $serverRequest = m::mock(ServerRequestInterface::class);
        $serverRequest->shouldReceive('getParsedBody')->andReturn([
            'grant_type' => 'authorization_code',
            'code' => $parseStr['code'],
            'client_id' => '123',
            'redirect_uri' => 'https://test123.nl'
        ]);

        $serverRequest->shouldReceive('getServerParams')->andReturn([]);
        $serverRequest->shouldReceive('hasHeader')->andReturn(false);

        $response = $controller->issueToken($serverRequest);

        $content = $response->content();

        $this->assertJson($content);

        $json = json_decode($content, true);

        $this->assertArrayHasKey('id_token', $json);
        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('token_type', $json);
        $this->assertArrayHasKey('expires_in', $json);
        $this->assertArrayHasKey('refresh_token', $json);
    }
}
