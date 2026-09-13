<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_generate_unique_name_returns_free_name_as_is(): void
    {
        $this->assertSame('john', resolve(UserService::class)->generateUniqueName(' john '));
    }

    public function test_generate_unique_name_adds_suffix_for_taken_name(): void
    {
        User::factory()->create(['name' => 'john']);
        User::factory()->create(['name' => 'john-2']);

        $this->assertSame('john-3', resolve(UserService::class)->generateUniqueName('john'));
    }

    public function test_generate_unique_name_uses_fallback_for_empty_name(): void
    {
        User::factory()->create(['name' => 'user']);

        $this->assertSame('user-2', resolve(UserService::class)->generateUniqueName(null));
    }

    public function test_generate_unique_name_replaces_slash(): void
    {
        $this->assertSame('john-doe', resolve(UserService::class)->generateUniqueName('john/doe'));
    }

    public function test_generate_unique_name_uses_fallback_for_reserved_name(): void
    {
        $this->assertSame('user', resolve(UserService::class)->generateUniqueName('..'));
    }
}
