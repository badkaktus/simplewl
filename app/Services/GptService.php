<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\GenerateGptDescriptionRequest;
use App\Services\Integration\DTO\GptResponseDTO;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

class GptService
{
    public function generateDescription(GenerateGptDescriptionRequest $request): GptResponseDTO
    {
        $apiKey = config('openai.api_key');
        if (Str::of($apiKey)->isEmpty() || Str::of(config('openai.model'))->isEmpty()) {
            return GptResponseDTO::createFailed('GPT service not configured');
        }

        $response = $this->makeRequest($this->createParams($request));

        return GptResponseDTO::createSuccess($response->choices[0]->message->content);
    }

    private function createParams(GenerateGptDescriptionRequest $request): array
    {
        return [
            'model' => config('openai.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional marketer with experience in writing texts that meet SEO rules. We have a service where users make their Wishlists and can share them. You need to generate a text for this user\'s wishlist using the product name, information from the provided URL (if available) and a description from the user (if available). The text should describe the benefits of this desire to the user and how it can benefit them. The text should be written directly on behalf of the user without unnecessary words. The text should be between 3 and 6 sentences in size and placed in a single paragraph. The text should be unique and not copied from the internet. The text should be written in English. The text should be written in a positive and encouraging manner. The text should be written in a way that encourages the user to take action.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->createUserMessage($request),
                ],
            ],
        ];
    }

    private function createUserMessage(GenerateGptDescriptionRequest $request): string
    {
        return sprintf('Type of text: a post for a website. Product Name: "%s".', $request->getTitle());
    }

    private function makeRequest(array $params): CreateResponse
    {
        return OpenAI::chat()->create($params);
    }
}
