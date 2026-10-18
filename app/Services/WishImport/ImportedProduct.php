<?php

declare(strict_types=1);

namespace App\Services\WishImport;

final readonly class ImportedProduct
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $imageUrl,
        public ?float $amount,
        public ?string $currency,
    ) {}

    /**
     * Without a title or an image the page is not a product page (e.g. a bot queue or a captcha).
     */
    public function isEmpty(): bool
    {
        return $this->title === null && $this->imageUrl === null;
    }

    public function withCurrency(?string $currency): self
    {
        return new self($this->title, $this->description, $this->imageUrl, $this->amount, $currency);
    }

    /**
     * @return array{title: ?string, description: ?string, imageUrl: ?string, amount: ?float, currency: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'imageUrl' => $this->imageUrl,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
