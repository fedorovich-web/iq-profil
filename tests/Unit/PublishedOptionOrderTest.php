<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PublishedOptionOrder;
use App\Support\PuzzleCatalog;
use PHPUnit\Framework\TestCase;

final class PublishedOptionOrderTest extends TestCase
{
    public function test_every_answer_key_has_a_nonperiodic_balanced_published_position(): void
    {
        $positions = [];

        foreach (PuzzleCatalog::definitions() as [$slug, $motif, $prompt, $cells, $candidates]) {
            $choices = PublishedOptionOrder::forQuestion($slug, $candidates);
            self::assertCount(6, $choices, $slug);
            self::assertCount(6, array_unique($choices), $slug);
            self::assertSame($choices, PublishedOptionOrder::forQuestion($slug, array_reverse($candidates)));
            $index = array_search($cells[8], $choices, true);
            self::assertIsInt($index);
            $positions[] = $index;
        }

        $counts = array_count_values($positions);
        self::assertCount(6, $counts);
        foreach ($counts as $count) {
            self::assertGreaterThanOrEqual(3, $count);
            self::assertLessThanOrEqual(5, $count);
        }
        self::assertNotSame(array_slice($positions, 0, 6), array_slice($positions, 6, 6));

        // For the previous release, keys repeated the same 6 positions.
        // At least 12 pairs now differ across a six-question offset.
        $differences = 0;
        for ($i = 6; $i < count($positions); $i++) {
            $differences += (int) ($positions[$i] !== $positions[$i - 6]);
        }
        self::assertGreaterThanOrEqual(12, $differences);
    }
}
