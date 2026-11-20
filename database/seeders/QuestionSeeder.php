<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\PublishedOptionOrder;
use App\Support\PuzzleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = PuzzleCatalog::definitions();
        if (count($definitions) !== PuzzleCatalog::COUNT) {
            throw new LogicException('The question catalog count does not match PuzzleCatalog::COUNT.');
        }

        DB::transaction(function () use ($definitions): void {
            $publishedCount = DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)
                ->where('bank_version', PuzzleCatalog::VERSION)->count();
            if ($publishedCount !== 0 && $publishedCount !== PuzzleCatalog::COUNT) {
                throw new LogicException('A published bank must contain exactly 25 questions.');
            }

            foreach ($definitions as $index => [$slug, $motif, $prompt, $cells, $candidates, $explanation]) {
                if (count($cells) !== 9 || count($candidates) !== 6
                    || count(array_unique($candidates)) !== 6
                    || ! in_array($cells[8], $candidates, true)) {
                    throw new LogicException('Invalid puzzle specification at position '.($index + 1));
                }

                $position = $index + 1;
                $version = PuzzleCatalog::VERSION;
                $fields = [
                    'test_code' => PuzzleCatalog::TEST_CODE,
                    'bank_version' => $version,
                    'position' => $position,
                    'kind' => $slug,
                    'prompt' => $prompt,
                    'cells' => json_encode($cells, JSON_THROW_ON_ERROR),
                    'explanation' => $explanation.' Это авторское упражнение на поиск закономерностей.',
                    'source' => 'IQ.Profil original vector puzzle v'.$version,
                ];
                $answer = $cells[8];
                // Canonical answer positions must not reveal a six-item cycle.
                // This release-specific permutation is immutable once published.
                $choices = PublishedOptionOrder::forQuestion($slug, $candidates);

                $row = DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)
                    ->where('bank_version', $version)->where('position', $position)->first();

                // Never alter a published question: existing attempt_answers refer to its ID.
                if ($row !== null) {
                    foreach ($fields as $key => $value) {
                        if ((string) $row->$key !== (string) $value) {
                            throw new LogicException("Question bank v{$version} was modified at position {$position}; publish a new revision.");
                        }
                    }
                    $existing = DB::table('answer_options')->where('question_id', $row->id)
                        ->orderBy('position')->get();
                    if ($existing->count() !== 6) {
                        throw new LogicException("Immutable question {$row->id} must have six choices.");
                    }
                    foreach ($existing as $slot => $option) {
                        if ((int) $option->position !== $slot + 1
                            || $option->code !== chr(65 + $slot)
                            || (int) $option->graphic_value !== $choices[$slot]
                            || (bool) $option->is_correct !== ($choices[$slot] === $answer)) {
                            throw new LogicException("Published answer options for question {$row->id} differ from bank v{$version}.");
                        }
                    }

                    continue;
                }

                $now = now();
                $questionId = DB::table('questions')->insertGetId([
                    ...$fields, 'created_at' => $now, 'updated_at' => $now,
                ]);

                foreach ($choices as $slot => $value) {
                    DB::table('answer_options')->insert([
                        'question_id' => $questionId,
                        'code' => chr(65 + $slot),
                        'position' => $slot + 1,
                        'graphic_value' => $value,
                        'is_correct' => $value === $answer,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
}
