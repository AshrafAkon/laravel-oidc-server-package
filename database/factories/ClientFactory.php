<?php

namespace AALP\Passport\Database\Factories;

// use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
// use Laravel\Passport\Client;
// use Laravel\Passport\Passport;
use Laravel\Passport\Database\Factories\ClientFactory as LaravelCientFactory;
use AALP\Passport\Model\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends LaravelCientFactory
{
    // /**
    //  * The name of the factory's corresponding model.
    //  *
    //  * @var string
    //  */
    protected $model = Client::class;

    // /**
    //  * Define the model's default state.
    //  *
    //  * @return array
    //  */
    public function definition()
    {
        return $this->ensurePrimaryKeyIsSet([
            'user_id' => null,
            'name' => $this->faker->company(),
            'secret' => Str::random(40),
            'redirect_uris' => [$this->faker->url()],
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
        ]);
    }
}
