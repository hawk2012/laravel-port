<?php

namespace Tests\Unit;

use App\Enums\AssetKind;
use App\Enums\GenerationStatus;
use App\Enums\GenerationType;
use App\Enums\Plan;
use App\Enums\ProjectStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnumsTest extends TestCase
{
    #[Test]
    #[DataProvider('creditCostProvider')]
    public function it_knows_credit_cost_per_type(GenerationType $type, int $expected): void
    {
        $this->assertSame($expected, $type->creditCost());
    }

    /**
     * @return array<string, array{GenerationType, int}>
     */
    public static function creditCostProvider(): array
    {
        return [
            'image' => [GenerationType::Image, 5],
            'video' => [GenerationType::Video, 40],
            'audio' => [GenerationType::Audio, 10],
            'text' => [GenerationType::Text, 1],
        ];
    }

    #[Test]
    public function generation_type_values_are_complete(): void
    {
        $this->assertSame(['image', 'video', 'audio', 'text'], GenerationType::values());
    }

    #[Test]
    public function it_classifies_final_and_running_statuses(): void
    {
        $this->assertTrue(GenerationStatus::Completed->isFinal());
        $this->assertTrue(GenerationStatus::Failed->isFinal());
        $this->assertTrue(GenerationStatus::Cancelled->isFinal());
        $this->assertFalse(GenerationStatus::Queued->isFinal());
        $this->assertFalse(GenerationStatus::Processing->isFinal());

        $this->assertTrue(GenerationStatus::Queued->isRunning());
        $this->assertTrue(GenerationStatus::Processing->isRunning());
        $this->assertFalse(GenerationStatus::Completed->isRunning());
    }

    #[Test]
    public function plan_limits_grow_with_tier(): void
    {
        $this->assertLessThan(Plan::Pro->concurrentGenerations(), Plan::Free->concurrentGenerations());
        $this->assertLessThan(Plan::Scale->concurrentGenerations(), Plan::Pro->concurrentGenerations());
        $this->assertLessThan(Plan::Pro->monthlyCredits(), Plan::Free->monthlyCredits());
        $this->assertLessThan(Plan::Scale->monthlyCredits(), Plan::Pro->monthlyCredits());
    }

    #[Test]
    #[DataProvider('assetKindProvider')]
    public function asset_kind_matches_generation_type(GenerationType $type, AssetKind $expected): void
    {
        $this->assertSame($expected, AssetKind::fromGenerationType($type));
    }

    /**
     * @return array<string, array{GenerationType, AssetKind}>
     */
    public static function assetKindProvider(): array
    {
        return [
            'image' => [GenerationType::Image, AssetKind::Image],
            'video' => [GenerationType::Video, AssetKind::Video],
            'audio' => [GenerationType::Audio, AssetKind::Audio],
            'text' => [GenerationType::Text, AssetKind::Text],
        ];
    }

    #[Test]
    public function project_status_has_two_states(): void
    {
        $this->assertSame(['active', 'archived'], ProjectStatus::values());
    }
}
