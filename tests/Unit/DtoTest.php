<?php

namespace Tests\Unit;

use App\DTO\CreateGenerationData;
use App\DTO\UsageSummary;
use App\Enums\GenerationType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DtoTest extends TestCase
{
    #[Test]
    public function it_builds_create_generation_data_from_validated_array(): void
    {
        $data = CreateGenerationData::fromArray([
            'type' => 'image',
            'prompt' => '  неоновый город  ',
            'negative_prompt' => 'размытие',
            'params' => ['size' => '1024x1024'],
            'idempotency_key' => 'draft-42',
        ], projectId: 7);

        $this->assertSame(7, $data->projectId);
        $this->assertSame(GenerationType::Image, $data->type);
        $this->assertSame('неоновый город', $data->prompt);
        $this->assertSame('размытие', $data->negativePrompt);
        $this->assertSame(['size' => '1024x1024'], $data->params);
        $this->assertSame('draft-42', $data->idempotencyKey);
    }

    #[Test]
    public function optional_fields_default_to_null_and_empty_array(): void
    {
        $data = CreateGenerationData::fromArray(['type' => 'text', 'prompt' => 'текст'], 1);

        $this->assertNull($data->negativePrompt);
        $this->assertNull($data->idempotencyKey);
        $this->assertSame([], $data->params);
    }

    #[Test]
    public function usage_summary_serializes_to_expected_keys(): void
    {
        $summary = new UsageSummary(
            generations: 10,
            completed: 8,
            failed: 2,
            creditsSpent: 64,
            inputTokens: 100,
            outputTokens: 250,
            avgLatencyMs: 1200,
            byType: ['image' => 6, 'text' => 4],
        );

        $this->assertSame([
            'generations' => 10,
            'completed' => 8,
            'failed' => 2,
            'credits_spent' => 64,
            'input_tokens' => 100,
            'output_tokens' => 250,
            'avg_latency_ms' => 1200,
            'by_type' => ['image' => 6, 'text' => 4],
        ], $summary->toArray());
    }
}
