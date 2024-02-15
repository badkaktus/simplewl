<?php

namespace App\Http\Controllers;

use App\Services\GptService;
use App\Services\Integration\DTO\GptResponseDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishDescriptionGenerateController extends Controller
{
    public function __construct(private GptService $gptService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->gptService->generateDescription());
    }
}
