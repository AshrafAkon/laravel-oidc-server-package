<?php

namespace AALP\Model;

use League\OAuth2\Server\Entities\UserEntityInterface;
use \Illuminate\Contracts\Auth\Authenticatable;

interface UserInterface extends  UserEntityInterface, Authenticatable
{
}
