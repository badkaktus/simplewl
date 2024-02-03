<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

abstract class AbstractThirdPartyAuthController extends TestCase
{
    protected function mockUser(string $driver, int $id, string $nickname, ?string $email = null): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')
            ->andReturn($id);
        $abstractUser
            ->shouldReceive('getNickname')
            ->andReturn($nickname);

        if (! is_null($email)) {
            $abstractUser
                ->shouldReceive('getEmail')
                ->andReturn($email);
        }

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }
}
