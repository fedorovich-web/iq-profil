<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PilotItemAnalysis
{
    /**
     * Descriptive, aggregated pilot data only. This is not population
     * norming, IQ estimation, or proof of psychometric validity.
     *
     * @return array<string, mixed>
     */
    public static function forRevision(int $revision): array
    {
        $questions = DB::table('questions')->where('bank_version', $revision)
            ->orderBy('position')->get(['position', 'kind']);

        if ($questions->count() !== PuzzleCatalog::COUNT) {
            throw new RuntimeException('A pilot analysis requires an immutable bank of 25 questions.');
        }

        $stats = [];
        foreach ($questions as $question) {
            $stats[(int) $question->position] = [
                'kind' => $question->kind,
                'n' => 0, 'correct' => 0, 'sum_rest' => 0,
                'sum_rest_squared' => 0, 'sum_correct_rest' => 0,
                'option_counts' => array_fill_keys(['A', 'B', 'C', 'D', 'E', 'F'], 0),
            ];
        }

        $completed = DB::table('quiz_attempts')->where('status', 'completed')
            ->where('question_revision', $revision)->count();

        $rows = DB::table('quiz_attempts as attempt')
            ->join('attempt_answers as answer', 'answer.attempt_id', '=', 'attempt.id')
            ->join('questions as question', 'question.id', '=', 'answer.question_id')
            ->join('answer_options as option', 'option.id', '=', 'answer.answer_option_id')
            ->where('attempt.status', 'completed')
            ->where('attempt.question_revision', $revision)
            ->where('question.bank_version', $revision)
            ->orderBy('attempt.id')
            ->orderBy('question.position')
            ->select([
                'attempt.id as attempt_id',
                'attempt.correct_count as stored_score',
                'question.position',
                'option.code',
                'option.is_correct',
            ])->cursor();

        $valid = 0;
        $invalid = 0;
        $attemptsWithAnswers = 0;
        $currentId = null;
        $responses = [];
        $flush = static function () use (&$responses, &$stats, &$valid, &$invalid, &$attemptsWithAnswers): void {
            if ($responses === []) {
                return;
            }

            $attemptsWithAnswers++;
            $positions = array_map(static fn ($entry): int => (int) $entry->position, $responses);
            $score = array_sum(array_map(static fn ($entry): int => (int) $entry->is_correct, $responses));
            $isComplete = count($responses) === PuzzleCatalog::COUNT
                && count(array_unique($positions)) === PuzzleCatalog::COUNT
                && $score === (int) $responses[0]->stored_score;

            if (! $isComplete) {
                $invalid++;
                $responses = [];

                return;
            }

            $valid++;
            foreach ($responses as $entry) {
                $position = (int) $entry->position;
                $correct = (int) $entry->is_correct;
                $rest = $score - $correct;
                $stats[$position]['n']++;
                $stats[$position]['correct'] += $correct;
                $stats[$position]['sum_rest'] += $rest;
                $stats[$position]['sum_rest_squared'] += $rest * $rest;
                $stats[$position]['sum_correct_rest'] += $correct * $rest;
                $stats[$position]['option_counts'][$entry->code]++;
            }
            $responses = [];
        };

        foreach ($rows as $row) {
            if ($currentId !== null && (int) $row->attempt_id !== $currentId) {
                $flush();
            }
            $currentId = (int) $row->attempt_id;
            $responses[] = $row;
        }
        $flush();

        $items = [];
        foreach ($stats as $position => $stat) {
            $n = $stat['n'];
            $correct = $stat['correct'];
            $restSum = $stat['sum_rest'];
            $restSquares = $stat['sum_rest_squared'];
            $cross = $stat['sum_correct_rest'];
            $denominator = sqrt(max(0, ($n * $correct - $correct * $correct)
                * ($n * $restSquares - $restSum * $restSum)));

            $items[] = [
                'position' => $position,
                'kind' => $stat['kind'],
                'sample_size' => $n,
                'correct_count' => $correct,
                'observed_correct_rate' => $n > 0 ? round($correct / $n, 4) : null,
                'corrected_item_total_correlation' => $n >= 100 && $denominator > 0
                    ? round(($n * $cross - $correct * $restSum) / $denominator, 4)
                    : null,
                'option_counts' => $stat['option_counts'],
            ];
        }

        return [
            'revision' => $revision,
            'total_questions' => PuzzleCatalog::COUNT,
            'completed_attempts' => $completed,
            'valid_complete_attempts' => $valid,
            'invalid_or_inconsistent_attempts' => $invalid + $completed - $attemptsWithAnswers,
            'items' => $items,
            'iq_score' => null,
            'population_norms' => false,
            'interpretation' => $valid < 100
                ? 'Exploratory sample too small for item-total correlations; no IQ or age norms.'
                : 'Descriptive self-selected pilot only; repeat attempts and sampling bias may affect estimates. No IQ norms.',
        ];
    }
}
