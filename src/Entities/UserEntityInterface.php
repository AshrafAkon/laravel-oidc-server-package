<?php

namespace AALP\OpenID\Entities;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property array $supportedClaims
 */
interface UserEntityInterface
{


    /**
     * Checks if User model can return $claim
     *
     * @param ClaimEntity $claim
     * @return bool
     */
    public function supportsClaim(ClaimEntityInterface $claim);
    /**
     * Checks if User model can return $claim
     *
     * @param int $id
     * @return UserEntityInterface
     */
    public function findForPassport($id);
    public function nickname(): Attribute;
    public function preferedUsername(): Attribute;
    public function emailVerified();
    public function locale(): Attribute;
    public function zoneinfo(): Attribute;
    public function name(): Attribute;
    public function givenName(): Attribute;
    public function familyName(): Attribute;
}
