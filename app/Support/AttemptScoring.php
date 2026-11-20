<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttemptScoring
{
    /**
     * Grade the saved answer set for an individual test and immutable revision.
     * No catalog-specific constants and no client-supplied marks are trusted.
     *
     * Call from a transaction while the owning quiz_attempts row is locked.
     */
    public static function grade(object $attempt): int
    {
        $expected = (int) $attempt->question_count;
        $revision = (int) $attempt->question_revision;
        $code = (string) $attempt->test_code;

        $questions = DB::table('questions')
            ->where('test_code', $code)
            ->where('bank_version', $revision)->count();

        abort_unless($expected > 0 && $questions === $expected, 409,
            'Состав вопросов изменился. Завершение теста приостановлено.');

        // Historical attempts from before fingerprints were introduced may be
        // graded from the archived revision; all new attempts are pinned.
        if ($attempt->bank_fingerprint !== null) {
            abort_unless(QuestionBankIntegrity::matches($attempt), 409,
                'Опубликованный набор вопросов был изменён. Подсчёт приостановлен.');
        }

        $valid = DB::table('attempt_answers as answers')
            ->join('questions as questions', function ($join) use ($code, $revision): void {
                $join->on('questions.id', '=', 'answers.question_id')
                    ->where('questions.test_code', '=', $code)
                    ->where('questions.bank_version', '=', $revision);
            })
            ->join('answer_options as options', function ($join): void {
                $join->on('options.id', '=', 'answers.answer_option_id')
                    ->on('options.question_id', '=', 'questions.id');
            })
            ->where('answers.attempt_id', $attempt->id)
            ->selectRaw('COUNT(*) AS total, COALESCE(SUM(CASE WHEN options.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct')
            ->first();

        if ((int) $valid->total !== $expected
            || DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count() !== $expected) {
            throw ValidationException::withMessages([
                'answer_option_id' => 'Не все ответы сохранены корректно. Обновите тест и проверьте выбранные варианты.',
            ]);
        }

        return (int) $valid->correct;
    }
}
