<?php

declare(strict_types=1);

namespace App\Services\WishImport;

final readonly class WishImportResult
{
    private function __construct(
        private ?ImportedProduct $product,
        private ?string $errorMessage,
    ) {}

    public static function success(ImportedProduct $product): self
    {
        return new self($product, null);
    }

    public static function failed(string $errorMessage): self
    {
        return new self(null, $errorMessage);
    }

    /**
     * @return array{isSuccess: true, data: array<string, mixed>}|array{isSuccess: false, errorMessage: string}
     */
    public function toArray(): array
    {
        if (! $this->product instanceof ImportedProduct) {
            return ['isSuccess' => false, 'errorMessage' => (string) $this->errorMessage];
        }

        return ['isSuccess' => true, 'data' => $this->product->toArray()];
    }
}
