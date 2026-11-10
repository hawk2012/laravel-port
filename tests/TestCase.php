<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Тесты не должны зависеть от собранных бандлов: в CI шаг сборки
        // фронтенда выполняется отдельным job'ом, а здесь важна логика.
        $this->withoutVite();
    }
}
