<?php

namespace AALP\Passport\Bridge;

use AALP\OpenID\Repositories\UserRepositoryInterface;
use AALP\OpenID\Repositories\UserRepositoryTrait;
use Illuminate\Auth\Middleware\Authenticate;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Bridge\UserRepository as LaravelUserRepository;
use League\OAuth2\Server\Entities\UserEntityInterface;
use RuntimeException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use AALP\Model\UserInterface;

class UserRepository extends LaravelUserRepository implements UserRepositoryInterface
{

    use UserRepositoryTrait;

    /**
     * Returns an associative array with attribute (claim) keys and values
     */
    public function getAttributes(UserEntityInterface $userEntity, $claims, $scopes)
    {
        $user = \App\Models\User::find($userEntity->getIdentifier());
        $attrs = [
            'sub' => strval($userEntity->getIdentifier()),
        ];

        // $userFields = array_merge($user->getFillable(), $user->getHidden());
        foreach ($claims as $claim) {
            if ($user->supportsClaim($claim)) {
                $attrs[$claim->getIdentifier()] = $user->{$claim->getIdentifier()};
            }
            // check if has property with this name
            // if (method_exists($user, $claim->getIdentifier())) {
            //     $attrs[$claim->getIdentifier()] = $user->{$claim->getIdentifier()}();
            // } elseif (in_array($claim->getIdentifier(), $userFields)) {
            //     $attrs[$claim->getIdentifier()] = $user->{$claim->getIdentifier()};
            //     //$userFields[$claim->getIdentifier()];
            // }
        }
        return $attrs;
    }

    public function getUserInfoAttributes(UserEntityInterface $user, $claims, $scopes)
    {
        return $this->getAttributes($user, $claims, $scopes);
    }

    public function getUserByIdentifier($identifier): ?UserEntityInterface
    {

        $provider = config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'findForPassport')) {
            $user = (new $model)->findForPassport($identifier);
        } else {
            $user = (new $model)->where('email', $identifier)->first();
        }

        if (!$user) {
            return null;
        }

        return new User($user->getAuthIdentifier());
    }
}
