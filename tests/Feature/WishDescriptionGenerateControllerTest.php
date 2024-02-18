<?php

namespace Tests\Feature;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

class WishDescriptionGenerateControllerTest extends TestCase
{
    public function test_successfully_generates_description(): void
    {
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

    public function test_validation_failed_title_is_required(): void
    {
        $response = $this->postJson('/generate-description', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(400);
        $response->assertExactJson(['title' => ['The title field is required.']]);
    }
}
