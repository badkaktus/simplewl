<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportWishRequest;
use App\Services\WishImport\WishImportService;
use Illuminate\Http\JsonResponse;

class WishImportController extends Controller
{
    public function __construct(private readonly WishImportService $wishImportService) {}

    public function __invoke(ImportWishRequest $request): JsonResponse
    {
        return response()->json($this->wishImportService->import($request->getUrl())->toArray());
    }
}
