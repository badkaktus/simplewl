<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\User;
use App\Models\UserAttributes;
use App\Models\Wish;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataFixMigrationsTest extends TestCase
{
    private const CURRENCIES_MIGRATION = 'database/migrations/2026_09_14_000000_clear_invalid_wish_currencies.php';

    private const USER_ATTRIBUTES_MIGRATION = 'database/migrations/2026_09_14_000001_add_unique_index_to_user_attributes_id.php';

    private const USER_NAMES_MIGRATION = 'database/migrations/2026_09_14_000002_make_user_names_url_safe.php';

    public function test_invalid_wish_currencies_are_cleared(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::CURRENCIES_MIGRATION]);

        $zeroCurrencyWish = Wish::factory()->create(['currency' => '0']);
        $validCurrencyWish = Wish::factory()->create(['currency' => 'EUR']);

        $this->artisan('migrate', ['--path' => self::CURRENCIES_MIGRATION]);

        $this->assertNull($zeroCurrencyWish->refresh()->currency);
        $this->assertSame('EUR', $validCurrencyWish->refresh()->currency);
    }

    public function test_duplicate_user_attributes_rows_are_merged(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::USER_ATTRIBUTES_MIGRATION]);

        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        DB::table('user_attributes')->insert([
            ['id' => $user->id, 'google_id' => 111, 'github_id' => null, 'created_at' => '2024-01-01 00:00:00', 'updated_at' => '2024-01-01 00:00:00'],
            ['id' => $user->id, 'google_id' => null, 'github_id' => '222', 'created_at' => '2024-02-01 00:00:00', 'updated_at' => '2024-02-01 00:00:00'],
            ['id' => $anotherUser->id, 'google_id' => 333, 'github_id' => null, 'created_at' => '2024-01-01 00:00:00', 'updated_at' => '2024-01-01 00:00:00'],
        ]);

        $this->artisan('migrate', ['--path' => self::USER_ATTRIBUTES_MIGRATION]);

        $this->assertSame(1, UserAttributes::where('id', $user->id)->count());
        $this->assertDatabaseHas('user_attributes', [
            'id' => $user->id,
            'google_id' => 111,
            'github_id' => '222',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-02-01 00:00:00',
        ]);
        $this->assertDatabaseHas('user_attributes', [
            'id' => $anotherUser->id,
            'google_id' => 333,
        ]);

        $this->expectException(QueryException::class);
        DB::table('user_attributes')->insert(['id' => $anotherUser->id]);
    }

    public function test_user_names_become_url_safe(): void
    {
        $slashUser = User::factory()->create(['name' => 'john/doe']);
        $takenNameUser = User::factory()->create(['name' => 'jane-doe']);
        $slashCollisionUser = User::factory()->create(['name' => 'jane/doe']);
        $dotUser = User::factory()->create(['name' => '..']);
        $validUser = User::factory()->create(['name' => 'valid name']);

        $this->artisan('migrate:rollback', ['--path' => self::USER_NAMES_MIGRATION]);
        $this->artisan('migrate', ['--path' => self::USER_NAMES_MIGRATION]);

        $this->assertSame('john-doe', $slashUser->refresh()->name);
        $this->assertSame('jane-doe', $takenNameUser->refresh()->name);
        $this->assertSame('jane-doe-'.$slashCollisionUser->id, $slashCollisionUser->refresh()->name);
        $this->assertSame('user', $dotUser->refresh()->name);
        $this->assertSame('valid name', $validUser->refresh()->name);
    }
}
