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
                $now = now();
                $row = DB::table('questions')->where('position', $position)->first();
                $fields = [
                    'kind' => $slug,
                    'prompt' => $prompt,
                    'cells' => json_encode($cells, JSON_THROW_ON_ERROR),
                    'explanation' => $explanation.' Это авторское тренировочное упражнение, не стандартизированная психометрическая методика.',
                    'source' => 'IQ.Profil original vector puzzle v'.PuzzleCatalog::VERSION,
                    'updated_at' => $now,
                ];

                if ($row === null) {
                    $questionId = DB::table('questions')->insertGetId([
                        ...$fields,
                        'position' => $position,
                        'created_at' => $now,
                    ]);
                    $existingOptions = collect();
                } else {
                    $questionId = $row->id;
                    DB::table('questions')->where('id', $questionId)->update($fields);
                    $existingOptions = DB::table('answer_options')
                        ->where('question_id', $questionId)->orderBy('position')->get();
                    if ($existingOptions->count() !== 6) {
                        throw new LogicException("Expected six existing options for question {$position}");
                    }
                }

                $answer = $cells[8];
                $choices = array_values(array_filter($candidates, static fn (int $value): bool => $value !== $answer));
                array_splice($choices, ($index * 5 + 2) % 6, 0, [$answer]);
                foreach ($choices as $slot => $value) {
                    $fields = [
                        'code' => chr(65 + $slot),
                        'position' => $slot + 1,
                        'graphic_value' => $value,
                        'is_correct' => $value === $answer,
                        'updated_at' => $now,
                    ];
                    if ($existingOptions->isNotEmpty()) {
                        DB::table('answer_options')->where('id', $existingOptions[$slot]->id)->update($fields);
                    } else {
                        DB::table('answer_options')->insert([
                            'question_id' => $questionId,
                            ...$fields,
                            'created_at' => $now,
                        ]);
                    }
                }
            }
        });
    }
}
