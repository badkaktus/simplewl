<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wish;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();
        $newEmail = fake()->safeEmail;
        $name = fake()->name;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $name,
                'email' => $newEmail,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame($name, $user->name);
        $this->assertSame($newEmail, $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_name_cant_be_changed_to_taken_name(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $anotherUser->name,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasErrors('name')
            ->assertRedirect('/profile');

        $this->assertNotSame($anotherUser->name, $user->refresh()->name);
    }

    public function test_profile_can_be_saved_with_unchanged_name(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_account_deletion_deletes_wish_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wishes/own.png', 'image');
        Storage::disk('public')->put('wishes/another.png', 'image');
        $user = User::factory()->create();
        Wish::factory()->create([
            'wishlist_id' => Wishlist::factory()->create(['user_id' => $user->id])->id,
            'local_file_name' => 'wishes/own.png',
        ]);
        Wish::factory()->create([
            'local_file_name' => 'wishes/another.png',
        ]);

        $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertRedirect('/');

        Storage::disk('public')->assertMissing('wishes/own.png');
        Storage::disk('public')->assertExists('wishes/another.png');
    }

    #[DataProvider('notUrlSafeNameProvider')]
    public function test_profile_name_must_be_url_safe(string $name): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $name,
                'email' => $user->email,
            ])
            ->assertSessionHasErrors('name');

        $this->assertNotSame($name, $user->refresh()->name);
    }

    public static function notUrlSafeNameProvider(): array
    {
        return [
            'slash' => ['john/doe'],
            'dot' => ['.'],
            'double dot' => ['..'],
        ];
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
