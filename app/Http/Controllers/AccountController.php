<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        abort_unless(in_array($section, ['overview', 'history', 'reports', 'subscription', 'profile', 'security', 'support'], true), 404);
        $attempts = DB::table('quiz_attempts as q')
            ->leftJoin('demo_payments as p', 'p.attempt_id', '=', 'q.id')
            ->where('q.user_id', $user->id)
            ->orderByDesc('q.started_at')
            ->select('q.*', 'p.status as payment_status')
            ->get();
        $latest = $attempts->first(fn ($a) => $a->status === 'completed' && $a->payment_status === 'succeeded');
        $finished = $attempts->where('status', 'completed');
        $demoActive = DB::table('demo_subscriptions as s')
            ->join('quiz_attempts as a', 'a.id', '=', 's.attempt_id')
            ->where('a.user_id', $user->id)
            ->where('s.status', 'active')->count();

        return view('account.home', compact('user', 'attempts', 'latest', 'finished', 'section', 'demoActive'));
    }

    public function export(Request $request, string $uuid): StreamedResponse
    {
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->where('user_id', $request->user()->id)->first();
        abort_unless($attempt !== null && $attempt->status === 'completed', 404);
        abort_unless(DB::table('demo_payments')->where('attempt_id', $attempt->id)->where('status', 'succeeded')->exists(), 403);

        $answers = DB::table('attempt_answers as a')
            ->join('questions as q', 'q.id', '=', 'a.question_id')
            ->join('answer_options as selected', 'selected.id', '=', 'a.answer_option_id')
            ->join('answer_options as correct', function ($join): void {
                $join->on('correct.question_id', '=', 'q.id')->where('correct.is_correct', true);
            })
            ->where('a.attempt_id', $attempt->id)
            ->orderBy('q.position')
            ->select('q.position', 'q.explanation', 'selected.code as answer', 'correct.code as expected')
            ->get();

        return response()->streamDownload(function () use ($attempt, $answers): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['IQ.Профиль — отчёт по заданию', $attempt->uuid], ';');
            fputcsv($output, ['Правильно', $attempt->correct_count, 'из 25'], ';');
            fputcsv($output, ['Время, секунд', $attempt->elapsed_seconds], ';');
            fputcsv($output, ['Вопрос', 'Ответ', 'Правильно', 'Объяснение'], ';');
            foreach ($answers as $answer) {
                fputcsv($output, [$answer->position, $answer->answer, $answer->expected, $answer->explanation], ';');
            }
            fclose($output);
        }, 'iq-profil-'.$attempt->uuid.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
