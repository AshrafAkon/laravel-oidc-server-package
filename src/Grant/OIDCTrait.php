<?php

namespace AALP\OpenID\Grant;

trait OIDCTrait
{
    protected $issuer;

    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }
}
