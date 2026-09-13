<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class AddUniqueIndexToUsersNameMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_09_13_000000_add_unique_index_to_users_name.php';

    public function test_migration_renames_duplicate_names_and_adds_unique_index(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION]);

        $first = User::factory()->create(['name' => 'john']);
        $second = User::factory()->create(['name' => 'john']);
        $third = User::factory()->create(['name' => 'john']);
        $other = User::factory()->create(['name' => 'jane']);

        $this->artisan('migrate', ['--path' => self::MIGRATION]);

        $this->assertSame('john', $first->refresh()->name);
        $this->assertSame('john-'.$second->id, $second->refresh()->name);
        $this->assertSame('john-'.$third->id, $third->refresh()->name);
        $this->assertSame('jane', $other->refresh()->name);

        $this->expectException(QueryException::class);
        User::factory()->create(['name' => 'jane']);
    }
}
