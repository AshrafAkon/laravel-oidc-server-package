<?php

namespace AALP\OpenID;

use AALP\OpenID\Repositories\AccessTokenRepositoryInterface;
use AALP\OpenID\Repositories\ClaimRepositoryInterface;
use AALP\OpenID\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserInfo
{

    protected $userRepository;
    protected $tokenRepository;
    protected $resourceServer;
    protected $claimRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        AccessTokenRepositoryInterface $tokenRepository,
        ResourceServer $resourceServer,
        ClaimRepositoryInterface $claimRepository
    ) {
        $this->userRepository = $userRepository;
        $this->tokenRepository = $tokenRepository;
        $this->resourceServer = $resourceServer;
        $this->claimRepository = $claimRepository;
    }

    public function respondToUserInfoRequest(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {

        $validated = $this->resourceServer->validateAuthenticatedRequest($request);

        $validated->getAttribute('oauth_access_token_id');
        $validated->getAttribute('oauth_user_id');

        $token = $this->tokenRepository->getAccessToken($validated->getAttribute('oauth_access_token_id'));

        $claimsRequested = $token->getClaims();

        foreach ($token->getScopes() as $scope) {
            $claims = $this->userRepository->getClaims(
                $this->claimRepository,
                $scope
            );
            if (count($claims) > 0) {
                array_push($claimsRequested, ...$claims);
            }
        }
        $user =  $this->userRepository->getUserByIdentifier(
            $validated->getAttribute('oauth_user_id')
        );
        $infoAttrs = $this->userRepository->getUserInfoAttributes(
            $user,
            $claimsRequested,
            $token->getScopes()
        );
        $response->getBody()->write(json_encode($infoAttrs));

        return $response->withAddedHeader('Content-Type', 'application/json');
    }
}
