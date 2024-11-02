<?php

declare(strict_types=1);

namespace App\Services\Integration\DTO;

class GptResponseDTO implements \JsonSerializable
{
    private bool $isSuccess;

    private ?string $response = null;

    private ?string $errorMessage = null;

    public function jsonSerialize(): array
    {
        return [
            'isSuccess' => $this->isSuccess,
            'response' => $this->response,
            'errorMessage' => $this->errorMessage,
        ];
    }

    public static function createSuccess(string $response): self
    {
        $dto = new self;
        $dto->response = $response;
        $dto->isSuccess = true;

        return $dto;
    }

    public static function createFailed(string $errorMessage): self
    {
        $dto = new self;
        $dto->errorMessage = $errorMessage;
        $dto->isSuccess = false;

        return $dto;
    }
}
