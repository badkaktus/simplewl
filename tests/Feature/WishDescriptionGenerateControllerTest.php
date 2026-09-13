<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WishDescriptionGenerateControllerTest extends TestCase
{
    public function test_successfully_generates_description(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'awesome!',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/generate-description', [
            'title' => 'Test title',
            'url' => 'https://example.com',
            'imageUrl' => 'https://example.com/image.jpg',
        ]);

        $response->assertStatus(200);
        $response->assertExactJson(['isSuccess' => true, 'response' => 'awesome!', 'errorMessage' => null]);
    }

    public function test_generation_is_limited_per_minute_for_each_user(): void
    {
        $this->fakeOpenAiResponses(RouteServiceProvider::GENERATE_DESCRIPTION_PER_MINUTE * 2 + 1);
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        for ($i = 0; $i < RouteServiceProvider::GENERATE_DESCRIPTION_PER_MINUTE; $i++) {
            $this->actingAs($user)->postJson('/generate-description', ['title' => 'Test title'])->assertOk();
        }

        $this->actingAs($user)->postJson('/generate-description', ['title' => 'Test title'])
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

        for ($i = 0; $i < RouteServiceProvider::GENERATE_DESCRIPTION_PER_MINUTE; $i++) {
            $this->actingAs($anotherUser)->postJson('/generate-description', ['title' => 'Test title'])->assertOk();
        }

        $this->travel(61)->seconds();

        $this->actingAs($user)->postJson('/generate-description', ['title' => 'Test title'])->assertOk();
    }

    public function test_generation_is_limited_per_day(): void
    {
        $this->fakeOpenAiResponses(RouteServiceProvider::GENERATE_DESCRIPTION_PER_DAY);
        $user = User::factory()->create();
        $this->be($user);

        for ($i = 0; $i < RouteServiceProvider::GENERATE_DESCRIPTION_PER_DAY; $i++) {
            if ($i > 0 && $i % RouteServiceProvider::GENERATE_DESCRIPTION_PER_MINUTE === 0) {
                $this->travel(61)->seconds();
            }

            $this->postJson('/generate-description', ['title' => 'Test title'])->assertOk();
        }

        $this->travel(61)->seconds();

        $this->postJson('/generate-description', ['title' => 'Test title'])
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_validation_failed_title_is_required(): void
    {
        $user = User::factory()->create();
        $this->be($user);

        $response = $this->postJson('/generate-description', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(400);
        $response->assertExactJson(['title' => ['The title field is required.']]);
    }

    public function test_non_auth_user_forbidden(): void
    {
        $response = $this->postJson('/generate-description', [
            'title' => 'Test title',
            'url' => 'https://example.com',
            'imageUrl' => 'https://example.com/image.jpg',
        ]);

        $response->assertStatus(401);
    }

    private function fakeOpenAiResponses(int $count): void
    {
        OpenAI::fake(array_fill(0, $count, CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => 'awesome!',
                    ],
                ],
            ],
        ])));
    }
}
