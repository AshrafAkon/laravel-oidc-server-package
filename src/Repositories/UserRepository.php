<?php

namespace AALP\OpenID\Repositories;

use AALP\OpenID\Repositories\UserRepositoryInterface;
use AALP\OpenID\Repositories\UserRepositoryTrait;

abstract class UserRepository implements UserRepositoryInterface
{
    use UserRepositoryTrait;
}
