<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GraphicPuzzle;
use App\Support\PuzzleCatalog;
use App\Support\PuzzleCatalogV5;
use App\Support\PuzzleCatalogV6;
use App\Support\PuzzleCatalogV7;
use PHPUnit\Framework\TestCase;

final class PuzzleCatalogTest extends TestCase
{
    public function test_every_question_has_six_visually_distinct_answers_and_one_key(): void
    {
        $definitions = PuzzleCatalog::definitions();
        self::assertSame(8, PuzzleCatalog::VERSION);
        self::assertCount(25, $definitions);
        self::assertCount(25, array_unique(array_column($definitions, 0)));

        $categories = array_count_values(array_map(
            static fn (array $definition): string => PuzzleCatalog::categoryFor($definition[0]),
            $definitions
        ));
        self::assertCount(5, $categories, 'The report must contain five meaningful areas.');
        foreach ($categories as $label => $count) {
            self::assertSame(5, $count, 'Incorrect category count: '.$label);
            self::assertNotSame('Графические задания', $label);
        }

        foreach ($definitions as $index => [$slug, $motif, $prompt, $cells, $candidates, $explanation]) {
            self::assertCount(9, $cells, $slug);
            self::assertCount(6, $candidates, $slug);
            self::assertCount(6, array_unique($candidates), $slug);
            self::assertContains($cells[8], $candidates, $slug);
            self::assertNotEmpty($prompt, $slug);
            self::assertNotEmpty($explanation, $slug);
            $svgs = array_map(static fn (int $value): string => (string) GraphicPuzzle::image($slug, $value), $candidates);
            self::assertCount(6, array_unique($svgs), 'Duplicate graphic options: '.$slug);
            $descriptions = array_map(static fn (int $value): string => GraphicPuzzle::name($slug, $value), $candidates);
            self::assertCount(6, array_unique($descriptions), 'Nonunique accessible names: '.$slug);
            foreach ($descriptions as $description) {
                self::assertStringNotContainsString('Неизвестн', $description, $slug);
            }
            self::assertDoesNotMatchRegularExpression(
                '/(?:вычтите|сложите|объедините|взаимно исключаются|взаимно удаляются|оставьте в третьей|соберите все)/ui',
                $prompt,
                'Question statement reveals the rule: '.$slug
            );
            foreach ($svgs as $svg) {
                self::assertStringContainsString('<svg ', $svg, $slug);
                self::assertStringContainsString('</svg>', $svg, $slug);
            }
            self::assertSame($cells[8], $candidates[array_search($cells[8], $candidates, true)], $slug.' '.($index + 1));
        }
    }

    public function test_all_150_candidates_are_evaluated_independently_of_saved_answer_keys(): void
    {
        foreach (PuzzleCatalog::definitions() as [$slug, $motif, $prompt, $matrix, $candidates]) {
            $correct = $matrix[8];
            $matches = 0;
            foreach ($candidates as $candidate) {
                $c = $matrix;
                $c[8] = $candidate;
                $rows = [array_slice($c, 0, 3), array_slice($c, 3, 3), array_slice($c, 6, 3)];
                $cols = [[$c[0], $c[3], $c[6]], [$c[1], $c[4], $c[7]], [$c[2], $c[5], $c[8]]];

                $valid = match ($slug) {
                    'dots-add' => self::every($rows, static fn (array $r): bool => $r[2] === $r[0] + $r[1]),
                    'nested-cycle' => self::step($rows, 2, 6) && self::step($cols, 2, 6),
                    'polygon-steps' => self::twoFeatureStep($rows, 1, 2, 3)
                        && self::twoFeatureStep($cols, 1, 1, 3),
                    'count-subtract' => self::every($rows, static fn (array $r): bool => $r[2] === $r[0] - $r[1]),
                    'direction-mirror' => self::every($rows, static fn (array $r): bool => $r[1] === 0 && $r[2] === (8 - $r[0]) % 8),
                    'shape-union', 'four-band-union' => self::every($rows, static fn (array $r): bool => ($r[0] | $r[1]) === $r[2]),
                    'color-count' => self::twoFeatureStep($rows, 0, 1, 3)
                        && self::twoFeatureStep($cols, 1, 0, 3),
                    'shape-common' => self::every($rows, static fn (array $r): bool => ($r[0] & $r[1]) === $r[2]),
                    'absolute-distance' => self::every($rows, static fn (array $r): bool => abs($r[0] - $r[1]) === $r[2]),
                    'orbit-skip' => self::orbitPairStep($rows, 2, 1)
                        && self::orbitPairStep($cols, 1, 3),
                    'compass-step' => self::step($rows, 2, 8) && self::step($cols, 3, 8),
                    'bar-wrap' => self::twoFeatureStep($rows, 2, 1, 3)
                        && self::twoFeatureStep($cols, 1, 1, 3),
                    'bar-difference' => self::every($rows, static fn (array $r): bool => ($r[0] & ~$r[1]) === $r[2]),
                    'rotation-composition' => self::every($rows, static fn (array $r): bool => ($r[0] + $r[1]) % 8 === $r[2]),
                    'bars-xor' => self::every($rows, static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    'mosaic-rotate-union' => self::every($rows, static fn (array $r): bool => (self::rotateMask($r[0], true) | self::rotateMask($r[1], false)) === $r[2]),
                    'shape-tone-cycle' => self::twoFeatureStep($rows, 2, 1, 3)
                        && self::twoFeatureStep($cols, 1, 1, 3),
                    'corners-dual' => self::cornerStep($rows, 1, 1)
                        && self::cornerStep($cols, 0, 1),
                    'grid-traversal' => self::step($rows, 2, 9) && self::step($cols, 3, 9),
                    'growing-strides' => self::growingStrides($rows, $cols),
                    'arrow-colors' => self::arrowToneStep($rows, 3, 2)
                        && self::arrowToneStep($cols, 2, 1),
                    'xor-two-axes' => self::every([...$rows, ...$cols], static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    'shape-size-cycle' => self::twoFeatureStep($rows, 1, 1, 3)
                        && self::twoFeatureStep($cols, 1, 2, 3),
                    'master-four-layer' => self::every($rows, static fn (array $r): bool => ($r[0] | $r[1]) === $r[2])
                        && self::every($cols, static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    default => false,
                };
                self::assertSame($candidate === $correct, $valid, "Multiple solutions or incorrect key: {$slug}, candidate {$candidate}");
                if ($valid) {
                    $matches++;
                }
            }
            self::assertSame(1, $matches, $slug.' must have exactly one intended solution.');
        }
    }

    public function test_new_distractors_preserve_all_published_revision_six_and_five_data(): void
    {
        self::assertSame(5, PuzzleCatalogV5::VERSION);
        self::assertSame(6, PuzzleCatalogV6::VERSION);
        self::assertSame(7, PuzzleCatalogV7::VERSION);
        self::assertSame(8, PuzzleCatalog::VERSION);

        $key = static fn (array $definitions, string $slug): array => array_values(array_filter($definitions, static fn (array $definition): bool => $definition[0] === $slug))[0];

        $old = $key(PuzzleCatalogV6::definitions(), 'shape-size-cycle');
        $current = $key(PuzzleCatalog::definitions(), 'shape-size-cycle');

        self::assertSame(1, $old[3][8]);
        self::assertSame(1, $current[3][8]);
        self::assertSame([0, 1, 2, 3, 5, 8], $old[4]);
        self::assertSame([0, 1, 2, 4, 7, 8], $current[4]);
        self::assertSame(3, count(array_filter($current[4],
            static fn (int $value): bool => $value % 3 === 1)),
            'The user must compare three differently sized squares, not spot the only square.'
        );
        self::assertNotEmpty($key(PuzzleCatalogV5::definitions(), 'mosaic-xor'));
        self::assertSame('mosaic-mask', PuzzleCatalog::find('mosaic-xor')[1]);
    }

    /** Rotate a four-cell mask in the same 2x2 arrangement as the artwork. */
    private static function rotateMask(int $mask, bool $clockwise): int
    {
        $mapping = $clockwise ? [2, 8, 1, 4] : [4, 1, 8, 2];
        $result = 0;
        foreach ([1, 2, 4, 8] as $index => $bit) {
            if (($mask & $bit) !== 0) {
                $result |= $mapping[$index];
            }
        }

        return $result;
    }

    private static function every(array $items, callable $condition): bool
    {
        foreach ($items as $item) {
            if (! $condition($item)) {
                return false;
            }
        }

        return true;
    }

    private static function step(array $groups, int $increment, int $modulus): bool
    {
        return self::every($groups, static fn (array $r): bool => $r[1] === ($r[0] + $increment) % $modulus
            && $r[2] === ($r[1] + $increment) % $modulus
        );
    }

    /**
     * State encoding is color * modulus + shape; each attribute follows
     * an independently checked modular transformation.
     */
    private static function twoFeatureStep(array $groups, int $shapeIncrement, int $toneIncrement, int $modulus): bool
    {
        return self::every($groups, static function (array $r) use ($shapeIncrement, $toneIncrement, $modulus): bool {
            for ($i = 0; $i < 2; $i++) {
                if ($r[$i + 1] % $modulus !== ($r[$i] % $modulus + $shapeIncrement) % $modulus
                    || intdiv($r[$i + 1], $modulus) !== (intdiv($r[$i], $modulus) + $toneIncrement) % $modulus) {
                    return false;
                }
            }

            return true;
        });
    }

    private static function orbitPairStep(array $groups, int $outerIncrement, int $innerIncrement): bool
    {
        return self::every($groups, static function (array $r) use ($outerIncrement, $innerIncrement): bool {
            for ($i = 0; $i < 2; $i++) {
                if (intdiv($r[$i + 1], 6) !== (intdiv($r[$i], 6) + $outerIncrement) % 6
                    || $r[$i + 1] % 6 !== ($r[$i] % 6 + $innerIncrement) % 6) {
                    return false;
                }
            }

            return true;
        });
    }

    private static function cornerStep(array $groups, int $angleIncrement, int $toneIncrement): bool
    {
        return self::every($groups, static function (array $r) use ($angleIncrement, $toneIncrement): bool {
            for ($i = 0; $i < 2; $i++) {
                if ($r[$i + 1] % 4 !== ($r[$i] % 4 + $angleIncrement) % 4
                    || intdiv($r[$i + 1], 4) !== (intdiv($r[$i], 4) + $toneIncrement) % 3) {
                    return false;
                }
            }

            return true;
        });
    }

    private static function arrowToneStep(array $groups, int $angleIncrement, int $toneIncrement): bool
    {
        return self::every($groups, static function (array $r) use ($angleIncrement, $toneIncrement): bool {
            for ($i = 0; $i < 2; $i++) {
                if ($r[$i + 1] % 8 !== ($r[$i] % 8 + $angleIncrement) % 8
                    || intdiv($r[$i + 1], 8) !== (intdiv($r[$i], 8) + $toneIncrement) % 3) {
                    return false;
                }
            }

            return true;
        });
    }

    private static function growingStrides(array $rows, array $cols): bool
    {
        foreach ($rows as $index => $row) {
            if ($index + 1 !== $row[1] - $row[0] || $index + 2 !== $row[2] - $row[1]) {
                return false;
            }
        }
        foreach ($cols as $index => $col) {
            if ($index + 2 !== $col[1] - $col[0] || $index + 3 !== $col[2] - $col[1]) {
                return false;
            }
        }

        return true;
    }
}
