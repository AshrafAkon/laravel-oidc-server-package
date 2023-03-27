<?php

namespace AALP\Passport;

use AALP\OpenID\Repositories\UserRepositoryInterface;
use AALP\OpenID\UserInfo;
use Illuminate\Http\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class UserInfoController
{
    public function userinfo(Request $request, UserRepositoryInterface $userRepository)
    {
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        /* @var $userinfo \AALP\OpenID\UserInfo */
        $userinfo = resolve(UserInfo::class);

        return $userinfo->respondToUserInfoRequest($psr, new Response());
    }
}
