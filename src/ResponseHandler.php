<?php

namespace AALP\OpenID;

use AALP\OpenID\RequestTypes\AuthenticationRequest;
use AALP\OpenID\ResponseHandlers\RedirectResponseHandler;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class ResponseHandler
{

    protected $handlers;

    public function __construct()
    {
        $this->handlers = [
            new RedirectResponseHandler()
        ];
    }

    public function getResponse(AuthenticationRequest $authenticationRequest, $code)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canRespondToAuthorizationRequest($authenticationRequest)) {
                $response = $handler->generateResponse($authenticationRequest, $code);
            }
        }

        if ($response == null) {
            throw OAuthServerException::invalidRequest('response_mode', 'No valid response_mode provided');
        }

        return $response;
    }
}
