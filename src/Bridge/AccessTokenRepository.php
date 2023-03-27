<?php

namespace AALP\Passport\Bridge;

use AALP\OpenID\Repositories\AccessTokenRepositoryInterface;
use AALP\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AccessToken as BridgeAccessToken;
use Laravel\Passport\Bridge\AccessTokenRepository as LaravelAccessTokenRepository;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class AccessTokenRepository extends LaravelAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function storeClaims(AccessTokenEntityInterface $token, array $claims)
    {
        $token = $this->tokenRepository->find($token->getIdentifier());
        $token->claims = $claims;
        $token->save();
    }

    public function getAccessToken($id)
    {
        $token = $this->tokenRepository->find($id);

        $claims = ClaimEntity::fromJsonArray($token->claims ?? []);

        return new AccessToken(
            $token->user_id,
            collect($token->scopes)->map(function ($scope) {
                return new Scope($scope);
            })->toArray(),
            new Client('not used', 'not used', 'not used', false),
            $claims
        );
    }
}
