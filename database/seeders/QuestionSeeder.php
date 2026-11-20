<?php

declare(strict_types=1);

namespace Database\Seeders;

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
            throw new LogicException('The catalog must contain exactly 25 questions.');
        }

        DB::transaction(function () use ($definitions): void {
            foreach ($definitions as $index => [$slug, $motif, $prompt, $cells, $candidates, $explanation]) {
                if (count($cells) !== 9 || count($candidates) !== 6
                    || count(array_unique($candidates)) !== 6
                    || ! in_array($cells[8], $candidates, true)) {
                    throw new LogicException('Invalid puzzle specification at position '.($index + 1));
                }

                $position = $index + 1;
                $version = PuzzleCatalog::VERSION;
                $fields = [
                    'bank_version' => $version,
                    'position' => $position,
                    'kind' => $slug,
                    'prompt' => $prompt,
                    'cells' => json_encode($cells, JSON_THROW_ON_ERROR),
                    'explanation' => $explanation.' Это авторское упражнение на поиск закономерностей.',
                    'source' => 'IQ.Profil original vector puzzle v'.$version,
                ];
                $row = DB::table('questions')->where('bank_version', $version)
                    ->where('position', $position)->first();

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

                    continue;
                }

                $now = now();
                $questionId = DB::table('questions')->insertGetId([
                    ...$fields, 'created_at' => $now, 'updated_at' => $now,
                ]);

                $answer = $cells[8];
                $choices = array_values(array_filter(
                    $candidates,
                    static fn (int $value): bool => $value !== $answer
                ));
                array_splice($choices, ($index * 5 + 2) % 6, 0, [$answer]);

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
