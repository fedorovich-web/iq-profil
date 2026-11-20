<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use LogicException;

final class QuestionBankIntegrity
{
    /**
     * Content-address a published test revision, including every answer key.
     * Exclude timestamps because routine database maintenance must not change a bank.
     *
     * Only server-side code may calculate or store this fingerprint.
     */
    public static function fingerprint(string $testCode, int $revision): string
    {
        $questions = DB::table('questions')
            ->where('test_code', $testCode)
            ->where('bank_version', $revision)
            ->orderBy('position')
            ->get(['id', 'position', 'kind', 'prompt', 'cells', 'explanation']);

        if ($questions->isEmpty()) {
            throw new LogicException('Cannot fingerprint an empty question bank.');
        }

        $options = DB::table('answer_options as option')
            ->join('questions as question', 'question.id', '=', 'option.question_id')
            ->where('question.test_code', $testCode)
            ->where('question.bank_version', $revision)
            ->orderBy('question.position')
            ->orderBy('option.position')
            ->get([
                'option.id', 'option.question_id', 'option.position',
                'option.code', 'option.graphic_value', 'option.is_correct',
            ]);

        $snapshot = [
            'test' => $testCode,
            'revision' => $revision,
            'questions' => $questions->map(static fn (object $question): array => [
                (int) $question->id,
                (int) $question->position,
                $question->kind,
                $question->prompt,
                $question->cells,
                $question->explanation,
            ])->all(),
            'options' => $options->map(static fn (object $option): array => [
                (int) $option->id,
                (int) $option->question_id,
                (int) $option->position,
                $option->code,
                (int) $option->graphic_value,
                (bool) $option->is_correct,
            ])->all(),
        ];

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public static function matches(object $attempt): bool
    {
        if (! is_string($attempt->bank_fingerprint ?? null)
            || ! preg_match('/^[0-9a-f]{64}$/D', $attempt->bank_fingerprint)) {
            return false;
        }

        return hash_equals(
            $attempt->bank_fingerprint,
            self::fingerprint((string) $attempt->test_code, (int) $attempt->question_revision)
        );
    }
}
