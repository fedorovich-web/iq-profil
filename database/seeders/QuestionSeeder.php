<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $families = ['dots', 'polygon', 'rotation', 'stripes', 'position'];
        $labels = [
            'dots' => 'Определите количество точек в пропущенной фигуре.',
            'polygon' => 'Найдите многоугольник, который завершает закономерность.',
            'rotation' => 'Определите направление стрелки в пустой клетке.',
            'stripes' => 'Выберите правильное количество вертикальных линий.',
            'position' => 'В какой точке сетки должен стоять синий маркер?',
        ];

        for ($index = 0; $index < 25; $index++) {
            $kind = $families[intdiv($index, 5)];
            $variant = $index % 5;
            $modulo = $kind === 'position' ? 9 : 6;
            $rowStep = 1 + $variant % 2;
            $columnStep = 1 + intdiv($variant, 2) % 2;
            $offset = ($variant * 2 + intdiv($index, 5)) % $modulo;
            $cells = [];

            for ($row = 0; $row < 3; $row++) {
                for ($column = 0; $column < 3; $column++) {
                    $cells[] = ($offset + $row * $rowStep + $column * $columnStep) % $modulo;
                }
            }

            $answer = $cells[8];
            $questionId = DB::table('questions')->insertGetId([
                'position' => $index + 1,
                'kind' => $kind,
                'prompt' => $labels[$kind],
                'explanation' => 'При движении вправо индекс состояния увеличивается на '.$columnStep.', вниз — на '.$rowStep.'. После последнего состояния последовательность повторяется. Пропущенный элемент имеет индекс '.($answer + 1).' из '.$modulo.'. Это учебное задание, не стандартизированная психометрическая методика.',
                'cells' => json_encode($cells, JSON_THROW_ON_ERROR),
                'source' => 'Original procedural matrix, independently generated for IQ.Profil',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $candidates = [$answer];
            for ($n = 1; count($candidates) < 6; $n++) {
                $v = ($answer + $n) % $modulo;
                if (! in_array($v, $candidates, true)) {
                    $candidates[] = $v;
                }
            }
            $correctSlot = ($index * 5 + 2) % 6;
            $choices = array_values(array_filter($candidates, fn (int $v): bool => $v !== $answer));
            array_splice($choices, $correctSlot, 0, [$answer]);

            foreach ($choices as $slot => $value) {
                DB::table('answer_options')->insert([
                    'question_id' => $questionId,
                    'code' => chr(65 + $slot),
                    'position' => $slot + 1,
                    'graphic_value' => $value,
                    'is_correct' => $value === $answer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
