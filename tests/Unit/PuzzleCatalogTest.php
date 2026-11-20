<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GraphicPuzzle;
use App\Support\PuzzleCatalog;
use PHPUnit\Framework\TestCase;

final class PuzzleCatalogTest extends TestCase
{
    public function test_every_question_has_six_visually_distinct_answers_and_one_key(): void
    {
        $definitions = PuzzleCatalog::definitions();
        self::assertCount(25, $definitions);
        self::assertCount(25, array_unique(array_column($definitions, 0)));

        $categories = array_count_values(array_map(
            static fn (array $definition): string => PuzzleCatalog::categoryFor($definition[0]),
            $definitions
        ));
        self::assertCount(5, $categories, 'The report must have five meaningful categories.');
        foreach ($categories as $label => $count) {
            self::assertSame(5, $count, 'The category has the wrong number of questions: '.$label);
            self::assertNotSame('Графические задания', $label);
        }

        foreach ($definitions as $index => [$slug, $motif, $prompt, $cells, $choices, $explanation]) {
            self::assertCount(9, $cells, $slug);
            self::assertCount(6, $choices, $slug);
            self::assertCount(6, array_unique($choices), $slug);
            self::assertContains($cells[8], $choices, $slug);
            self::assertNotEmpty($prompt, $slug);
            self::assertNotEmpty($explanation, $slug);
            $svgs = array_map(static fn (int $value): string => (string) GraphicPuzzle::image($slug, $value), $choices);
            self::assertCount(6, array_unique($svgs), 'Two answer illustrations are identical for '.$slug);
            $descriptions = array_map(static fn (int $value): string => GraphicPuzzle::name($slug, $value), $choices);
            self::assertCount(6, array_unique($descriptions), 'Two candidates share the same semantic description: '.$slug);
            foreach ($descriptions as $description) {
                self::assertStringNotContainsString('Неизвестн', $description, $slug);
                self::assertStringNotContainsString('Композиция номер', $description, $slug);
            }
            self::assertDoesNotMatchRegularExpression('/(?:вычтите|сложите|объедините|взаимно исключаются|взаимно удаляются|оставьте в третьей|соберите все цветные полосы)/ui', $prompt, 'The prompt reveals its answer rule: '.$slug);
            foreach ($svgs as $svg) {
                self::assertStringContainsString('<svg ', $svg, $slug);
                self::assertStringContainsString('</svg>', $svg, $slug);
                self::assertGreaterThan(90, strlen($svg), $slug);
            }
            self::assertSame($cells[8], $choices[array_search($cells[8], $choices, true)], $slug.' '.($index + 1));
        }
    }

    public function test_all_twenty_five_intended_rules_are_satisfied(): void
    {
        foreach (PuzzleCatalog::definitions() as [$slug, $motif, $prompt, $c, $candidates]) {
            $correct = $c[8];
            foreach ($candidates as $candidate) {
                $c[8] = $candidate;
                $rows = [array_slice($c, 0, 3), array_slice($c, 3, 3), array_slice($c, 6, 3)];
                $cols = [[$c[0], $c[3], $c[6]], [$c[1], $c[4], $c[7]], [$c[2], $c[5], $c[8]]];

                $valid = match ($slug) {
                    'dots-add' => self::every($rows, static fn (array $r): bool => $r[2] === $r[0] + $r[1]),
                    'compass-step' => self::step($rows, 1, 6) && self::step($cols, 2, 6),
                    'mosaic-xor' => self::every($rows, static fn (array $r): bool => ([3, 5, 9, 6, 10, 12][$r[0]] ^ [3, 5, 9, 6, 10, 12][$r[1]]) === [3, 5, 9, 6, 10, 12][$r[2]]),
                    'polygon-steps' => self::step($rows, 1) && self::step($cols, 1),
                    'orbit-skip' => self::step($rows, 2, 6) && self::step($cols, 1, 6),
                    'shape-union', 'four-band-union' => self::every($rows, static fn (array $r): bool => ($r[0] | $r[1]) === $r[2]),
                    'bars-xor' => self::every($rows, static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    'shape-common' => self::every($rows, static fn (array $r): bool => ($r[0] & $r[1]) === $r[2]),
                    'count-subtract' => self::every($rows, static fn (array $r): bool => $r[2] === $r[0] - $r[1]),
                    'direction-mirror' => self::every($rows, static fn (array $r): bool => (6 - $r[0]) % 6 === $r[2]),
                    'rotation-composition' => self::every($rows, static fn (array $r): bool => ($r[0] + $r[1]) % 6 === $r[2]),
                    'color-latin', 'nested-cycle' => self::latin($rows) && self::latin($cols),
                    'shape-tone-cycle' => self::every($cols, static fn (array $col): bool => intdiv($col[0], 3) === intdiv($col[1], 3) && intdiv($col[1], 3) === intdiv($col[2], 3))
                        && self::every($rows, static fn (array $group): bool => $group[1] % 3 === ($group[0] + 1) % 3 && $group[2] % 3 === ($group[1] + 1) % 3)
                        && self::every($cols, static fn (array $group): bool => $group[1] % 3 === ($group[0] + 2) % 3 && $group[2] % 3 === ($group[1] + 2) % 3),
                    'bar-complement' => self::every($rows, static fn (array $r): bool => ($r[0] & $r[1]) === 0 && ($r[0] | $r[1]) === 7 && $r[2] === 7),
                    'bar-difference' => self::every($rows, static fn (array $r): bool => ($r[0] & ~$r[1]) === $r[2]),
                    'corners-dual' => self::step($rows, 1) && self::step($cols, 4),
                    'bar-wrap' => self::step($rows, 2, 6) && self::step($cols, 1, 6),
                    'xor-two-axes', 'xor-four' => self::every([...$rows, ...$cols], static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    'absolute-distance' => self::every($rows, static fn (array $r): bool => abs($r[0] - $r[1]) === $r[2]),
                    'growing-strides' => self::every(array_keys($rows), static fn (int $i): bool => $rows[$i][1] - $rows[$i][0] === $i + 1 && $rows[$i][2] - $rows[$i][1] === $i + 1) && self::every(array_keys($cols), static fn (int $i): bool => $cols[$i][1] - $cols[$i][0] === $i + 1 && $cols[$i][2] - $cols[$i][1] === $i + 1),
                    'grid-traversal' => self::step($rows, 3, 9) && self::step($cols, 4, 9),
                    'color-count' => self::every($rows, static fn (array $r): bool => intdiv($r[0], 3) === intdiv($r[1], 3) && intdiv($r[1], 3) === intdiv($r[2], 3)) && self::every($cols, static fn (array $r): bool => $r[0] % 3 === $r[1] % 3 && $r[1] % 3 === $r[2] % 3),
                    'arrow-colors' => self::every($rows, static fn (array $r): bool => self::toneDirectionStep($r, 2, 1))
                        && self::every($cols, static fn (array $r): bool => self::toneDirectionStep($r, 1, 1)),
                    'master-four-layer' => self::every($rows, static fn (array $r): bool => ($r[0] | $r[1]) === $r[2])
                        && self::every($cols, static fn (array $r): bool => ($r[0] ^ $r[1]) === $r[2]),
                    default => false,
                };
                self::assertSame($candidate === $correct, $valid, "Ambiguous or invalid visual rule: {$slug}, candidate {$candidate}");
            }
        }
    }

    private static function every(array $list, callable $predicate): bool
    {
        foreach ($list as $item) {
            if (! $predicate($item)) {
                return false;
            }
        }

        return true;
    }

    private static function step(array $groups, int $step, ?int $mod = null): bool
    {
        return self::every($groups, static function (array $group) use ($step, $mod): bool {
            return $group[1] === ($mod === null ? $group[0] + $step : ($group[0] + $step) % $mod)
                && $group[2] === ($mod === null ? $group[1] + $step : ($group[1] + $step) % $mod);
        });
    }

    private static function toneDirectionStep(array $group, int $toneStep, int $directionStep): bool
    {
        for ($i = 0; $i < 2; $i++) {
            if (intdiv($group[$i + 1], 3) !== (intdiv($group[$i], 3) + $toneStep) % 3
                || $group[$i + 1] % 3 !== ($group[$i] % 3 + $directionStep) % 3) {
                return false;
            }
        }

        return true;
    }

    private static function latin(array $groups): bool
    {
        return self::every($groups, static function (array $r): bool {
            sort($r);

            return $r === [0, 1, 2];
        });
    }
}
