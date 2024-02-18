<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateGptDescriptionRequest;
use App\Services\GptService;
use Illuminate\Http\JsonResponse;

class WishDescriptionGenerateController extends Controller
{
    public function __construct(private readonly GptService $gptService)
    {
    }

    public function __invoke(GenerateGptDescriptionRequest $request): JsonResponse
    {
        $response = $this->gptService->generateDescription($request);

        return response()->json($response);
    }
}
