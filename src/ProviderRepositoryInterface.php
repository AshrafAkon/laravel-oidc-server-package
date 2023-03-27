<?php

namespace AALP\Passport;

use Illuminate\Http\Request;

interface ProviderRepositoryInterface
{

    /**
     * @return ProviderInterface
     */
    public function get();

    public function update(Request $request);
}
