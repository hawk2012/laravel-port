<?php

namespace App\Exceptions;

use RuntimeException;

class ConcurrencyLimitException extends RuntimeException
{
    public function __construct(public readonly int $limit)
    {
        parent::__construct("Достигнут лимит одновременных генераций ({$limit}). Дождитесь завершения текущих задач.");
    }
}
