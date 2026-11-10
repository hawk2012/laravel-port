<?php

namespace App\Exceptions;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = true,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function unreachable(string $reason, ?\Throwable $previous = null): self
    {
        return new self("AI-провайдер недоступен: {$reason}", true, $previous);
    }

    public static function rejected(string $reason): self
    {
        return new self("AI-провайдер отклонил запрос: {$reason}", false);
    }
}
