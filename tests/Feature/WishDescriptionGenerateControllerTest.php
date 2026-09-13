<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\GptService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Laravel\Testing\OpenAIFake;
use OpenAI\Resources\Chat;
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

    public function test_openai_error_returns_failed_response(): void
    {
        $this->be(User::factory()->create());
        OpenAI::fake([
            new ErrorException(['message' => 'The server had an error', 'type' => 'server_error', 'code' => null], new Psr7Response(500)),
        ]);

        $response = $this->postJson('/generate-description', [
            'title' => 'Test title',
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'isSuccess' => false,
            'response' => null,
            'errorMessage' => GptService::GENERATION_FAILED_MESSAGE,
        ]);
    }

    public function test_empty_openai_answer_returns_failed_response(): void
    {
        $this->be(User::factory()->create());
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => '   ',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/generate-description', [
            'title' => 'Test title',
        ]);

        $response->assertOk();
        $response->assertJson([
            'isSuccess' => false,
            'errorMessage' => GptService::GENERATION_FAILED_MESSAGE,
        ]);
    }

    public function test_prompt_contains_url_and_user_description(): void
    {
        $this->be(User::factory()->create());
        $openAi = $this->fakeOpenAiResponses(1);

        $this->postJson('/generate-description', [
            'title' => 'Велосипед',
            'description' => 'Хочу ездить на работу',
            'url' => 'https://shop.test/bike',
        ])->assertOk();

        $openAi->assertSent(Chat::class, function (string $method, array $parameters): bool {
            $systemMessage = $parameters['messages'][0]['content'];
            $userMessage = $parameters['messages'][1]['content'];

            return str_contains($systemMessage, 'same language as the product name')
                && ! str_contains($systemMessage, 'written in English')
                && str_contains($userMessage, 'Product Name: "Велосипед"')
                && str_contains($userMessage, 'Product URL: "https://shop.test/bike"')
                && str_contains($userMessage, 'User description: "Хочу ездить на работу"');
        });
    }

    public function test_empty_optional_fields_pass_validation(): void
    {
        $this->be(User::factory()->create());
        $openAi = $this->fakeOpenAiResponses(1);

        $response = $this->postJson('/generate-description', [
            'title' => 'Test title',
            'description' => null,
            'url' => null,
        ]);

        $response->assertOk();
        $openAi->assertSent(Chat::class, fn (string $method, array $parameters): bool => ! str_contains((string) $parameters['messages'][1]['content'], 'Product URL'));
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

    private function fakeOpenAiResponses(int $count): OpenAIFake
    {
        return OpenAI::fake(array_fill(0, $count, CreateResponse::fake([
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
