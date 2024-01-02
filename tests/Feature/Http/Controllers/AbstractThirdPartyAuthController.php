<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

abstract class AbstractThirdPartyAuthController extends TestCase
{
    protected function mockUser(string $driver, int $id, string $nickname, string $email): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')
            ->andReturn($id)
            ->shouldReceive('getEmail')
            ->andReturn($email)
            ->shouldReceive('getNickname')
            ->andReturn($nickname);

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }
}
