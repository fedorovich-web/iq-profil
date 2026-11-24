<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\PuzzleCatalog;
use App\Support\QuestionBankIntegrity;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $section = $request->query('section', 'overview');
        abort_unless(in_array($section, [
            'overview', 'history', 'reports', 'subscription', 'profile', 'security', 'support',
        ], true), 404);

        $attempts = collect();
        $reports = collect();
        $latest = null;
        $demoActive = 0;

        if ($section === 'overview') {
            $latest = $this->attemptsQuery($user->id)
                ->where('q.status', 'completed')->where('p.status', 'succeeded')
                ->orderByDesc('q.completed_at')->orderByDesc('q.id')->first();
            $attempts = $this->attemptsQuery($user->id)
                ->orderByRaw('COALESCE(q.completed_at, q.updated_at, q.started_at) DESC')->orderByDesc('q.id')->limit(3)->get();
            $reports = $this->attemptsQuery($user->id)
                ->where('q.status', 'completed')->where('p.status', 'succeeded')
                ->orderByDesc('q.completed_at')->orderByDesc('q.id')->limit(3)->get();
        } elseif ($section === 'history') {
            $attempts = $this->attemptsQuery($user->id)
                ->orderByRaw('COALESCE(q.completed_at, q.updated_at, q.started_at) DESC')->orderByDesc('q.id')
                ->paginate(15, ['*'], 'historyPage')->withQueryString();
        } elseif ($section === 'reports') {
            $reports = $this->attemptsQuery($user->id)
                ->where('q.status', 'completed')->where('p.status', 'succeeded')
                ->orderByDesc('q.completed_at')->orderByDesc('q.id')
                ->paginate(15, ['*'], 'reportsPage')->withQueryString();
        }

        if ($section === 'overview' || $section === 'subscription') {
            $demoActive = DB::table('demo_subscriptions as s')
                ->join('quiz_attempts as a', 'a.id', '=', 's.attempt_id')
                ->where('a.user_id', $user->id)
                ->where('s.status', 'active')->count();
        }

        return view('account.home', compact('user', 'attempts', 'reports', 'latest', 'section', 'demoActive'));
    }

    private function attemptsQuery(int $userId): Builder
    {
        return DB::table('quiz_attempts as q')
            ->leftJoin('demo_payments as p', 'p.attempt_id', '=', 'q.id')
            ->where('q.user_id', $userId)
            ->select('q.*', 'p.status as payment_status');
    }

    public function export(Request $request, string $uuid): StreamedResponse
    {
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->where('user_id', $request->user()->id)->first();
        abort_unless($attempt !== null && $attempt->status === 'completed', 404);
        abort_unless(DB::table('demo_payments')->where('attempt_id', $attempt->id)->where('status', 'succeeded')->exists(), 403);

        $archivedCount = DB::table('questions')->where('test_code', $attempt->test_code)
            ->where('bank_version', $attempt->question_revision)->count();
        $questionCount = (int) ($attempt->question_count ?: $archivedCount);

        $answers = DB::table('attempt_answers as a')
            ->join('questions as q', function ($join) use ($attempt): void {
                $join->on('q.id', '=', 'a.question_id')
                    ->where('q.test_code', '=', $attempt->test_code)
                    ->where('q.bank_version', '=', $attempt->question_revision);
            })
            ->join('answer_options as selected', function ($join): void {
                $join->on('selected.id', '=', 'a.answer_option_id')
                    ->on('selected.question_id', '=', 'q.id');
            })
            ->where('a.attempt_id', $attempt->id)
            ->select('q.kind', 'selected.is_correct')
            ->get();

        $hasValidBreakdown = $questionCount > 0
            && ($attempt->bank_fingerprint === null
                || ($archivedCount > 0 && QuestionBankIntegrity::matches($attempt)))
            && $answers->count() === $questionCount
            && DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count() === $questionCount
            && $answers->where('is_correct', 1)->count() === (int) $attempt->correct_count;

        $categoryBreakdown = $hasValidBreakdown
            ? $answers->groupBy(fn ($item): string => PuzzleCatalog::categoryFor($item->kind))
                ->map(fn ($group): array => [
                    'correct' => $group->where('is_correct', 1)->count(),
                    'total' => $group->count(),
                ])
            : collect();

        return response()->streamDownload(function () use ($attempt, $categoryBreakdown, $hasValidBreakdown, $questionCount): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['IQ.Профиль — сводка прохождения', $attempt->uuid], ';');
            fputcsv($output, ['Код теста', $attempt->test_code], ';');
            fputcsv($output, ['Ревизия вопросов', $attempt->question_revision], ';');
            fputcsv($output, ['Всего заданий', $questionCount > 0 ? $questionCount : 'Нет подтверждённых данных'], ';');
            fputcsv($output, ['Правильных решений', $attempt->correct_count], ';');
            fputcsv($output, ['Время, секунд', $attempt->elapsed_seconds], ';');
            if ($hasValidBreakdown) {
                fputcsv($output, ['Категория', 'Правильно', 'Всего'], ';');
                foreach ($categoryBreakdown as $label => $group) {
                    fputcsv($output, [$label, $group['correct'], $group['total']], ';');
                }
            } else {
                fputcsv($output, ['Примечание', 'Подробная разбивка недоступна: архивные ответы отсутствуют или не прошли проверку целостности.'], ';');
            }
            fclose($output);
        }, 'iq-profil-'.$attempt->uuid.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
