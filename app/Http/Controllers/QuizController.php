<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\AttemptScoring;
use App\Support\AttemptTimer;
use App\Support\DemoSubscriptionLifecycle;
use App\Support\OptionPresentation;
use App\Support\PuzzleCatalog;
use App\Support\PuzzleCatalogV5;
use App\Support\PuzzleCatalogV6;
use App\Support\PuzzleCatalogV7;
use App\Support\QuestionBankIntegrity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuizController extends Controller
{
    public function start(Request $request, ?string $preferredUuid = null): RedirectResponse
    {
        $this->assertCurrentQuestionBank();

        $attempt = DB::transaction(function () use ($request, $preferredUuid): object {
            // Two simultaneous clicks must not create two unpaid attempts.
            // A completed unpaid attempt takes precedence over a newer unfinished one.
            DemoSubscriptionLifecycle::lockOwner($request);
            $query = DB::table('quiz_attempts as a')
                ->where('a.test_code', PuzzleCatalog::TEST_CODE)
                ->leftJoin('demo_payments as p', function ($join): void {
                    $join->on('p.attempt_id', '=', 'a.id')->where('p.status', '=', 'succeeded');
                });

            if ($request->user()) {
                $query->where('a.user_id', $request->user()->id);
            } else {
                $query->whereNull('a.user_id')->whereIn('a.uuid', $request->session()->get('owned_attempts', []));
            }

            $lastCompletedId = (clone $query)->where('a.status', 'completed')->max('a.id') ?? 0;
            $blocked = $query->where(function ($query) use ($lastCompletedId): void {
                $query->where(function ($query): void {
                    $query->where('a.status', 'completed')->whereNull('p.id');
                })->orWhere(function ($query) use ($lastCompletedId): void {
                    $query->where('a.question_revision', PuzzleCatalog::VERSION)
                        ->where('a.id', '>', $lastCompletedId)
                        ->whereIn('a.status', ['in_progress', 'expired']);
                });
            })->orderByRaw("CASE WHEN a.status = 'completed' THEN 0 WHEN a.uuid = ? THEN 1 WHEN EXISTS (SELECT 1 FROM attempt_answers AS aa WHERE aa.attempt_id = a.id) THEN 2 ELSE 3 END", [$preferredUuid ?? ''])
                ->orderByDesc('a.id')
                ->select('a.id', 'a.uuid', 'a.status')->first();

            if ($blocked !== null) {
                // Recover the most advanced eligible attempt affected by the old
                // expiry policy. A completed result always remains immutable.
                if ($blocked->status === 'expired') {
                    DB::table('quiz_attempts')->where('id', $blocked->id)
                        ->where('status', 'expired')->update(['status' => 'in_progress']);
                    $blocked->status = 'in_progress';
                }

                return $blocked;
            }

            $uuid = (string) Str::uuid();
            DB::table('quiz_attempts')->insert([
                'uuid' => $uuid,
                'user_id' => $request->user()?->id,
                'status' => 'in_progress',
                'test_code' => PuzzleCatalog::TEST_CODE,
                'question_revision' => PuzzleCatalog::VERSION,
                'bank_fingerprint' => QuestionBankIntegrity::fingerprint(PuzzleCatalog::TEST_CODE, PuzzleCatalog::VERSION),
                'option_shuffle_seed' => bin2hex(random_bytes(16)),
                'question_count' => DB::table('questions')
                    ->where('test_code', PuzzleCatalog::TEST_CODE)
                    ->where('bank_version', PuzzleCatalog::VERSION)->count(),
                'current_position' => 1,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (object) ['uuid' => $uuid, 'status' => 'in_progress'];
        }, 3);

        $owned = $request->session()->get('owned_attempts', []);
        $owned[] = $attempt->uuid;
        $request->session()->put('owned_attempts', array_values(array_unique($owned)));
        $request->session()->put('active_attempt', $attempt->uuid);

        return $attempt->status === 'completed'
            ? redirect()->route('paywall', $attempt->uuid)
                ->with('quiz_gate_notice', 'Предыдущая попытка завершена. Сначала откройте её результат — после демо-подтверждения можно начать новый тест.')
            : redirect()->route('quiz.show');
    }

    public function resume(Request $request, string $uuid): RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return $this->start($request, $uuid);
        }
        $request->session()->put('active_attempt', $uuid);

        return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', ['uuid' => $uuid]);
    }

    public function intake(Request $request, string $step): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', $attempt->uuid);
        }
        if ($attempt->status !== 'in_progress' || (int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
            return $this->start($request);
        }
        if ($step === 'age' && $attempt->gender === null) {
            return redirect()->route('quiz.intake', ['step' => 'gender']);
        }
        if ($step === 'gender' && $attempt->age_group !== null) {
            // Revisiting the first screen is allowed; the saved value remains selected.
        }

        return view('intake', compact('attempt', 'step'));
    }

    public function saveIntake(Request $request, string $step): RedirectResponse
    {
        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status === 'expired') {
            return $this->start($request);
        }
        abort_unless($attempt->status === 'in_progress'
            && (int) $attempt->question_revision === PuzzleCatalog::VERSION, 409);

        if ($step === 'gender') {
            $validated = $request->validate(['gender' => 'required|in:male,female,unspecified']);
            DB::table('quiz_attempts')->where('id', $attempt->id)->update([
                'gender' => $validated['gender'],
                'updated_at' => now(),
            ]);

            return redirect()->route('quiz.intake', ['step' => 'age']);
        }

        abort_unless($attempt->gender !== null, 409);
        $validated = $request->validate(['age_group' => 'required|in:18_24,25_34,35_44,45_54,55_plus']);
        DB::table('quiz_attempts')->where('id', $attempt->id)->update([
            'age_group' => $validated['age_group'],
            'profile_completed_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('quiz.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $uuid = $request->session()->get('active_attempt');
        if (! is_string($uuid) && ($request->user() || $request->session()->get('owned_attempts', []) !== [])) {
            return $this->start($request);
        }

        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed' && ($attempt->status !== 'in_progress'
            || (int) $attempt->question_revision !== PuzzleCatalog::VERSION)) {
            return $this->start($request, $attempt->uuid);
        }
        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', ['uuid' => $attempt->uuid]);
        }

        $this->assertCurrentQuestionBank();
        if ($attempt->bank_fingerprint !== null) {
            abort_unless(QuestionBankIntegrity::matches($attempt), 409,
                'Опубликованный набор вопросов изменился. Дождитесь восстановления версии теста.');
        }

        if ($attempt->gender === null) {
            return redirect()->route('quiz.intake', ['step' => 'gender']);
        }
        if ($attempt->age_group === null) {
            return redirect()->route('quiz.intake', ['step' => 'age']);
        }

        // A pre-test intake never contributes to elapsed_seconds.
        if ($attempt->quiz_started_at === null) {
            $now = now();
            DB::table('quiz_attempts')->where('id', $attempt->id)
                ->where('status', 'in_progress')->whereNull('quiz_started_at')
                ->update(['quiz_started_at' => $now, 'elapsed_seconds' => 0, 'updated_at' => $now]);
            $attempt = DB::table('quiz_attempts')->where('id', $attempt->id)->first();
            abort_unless($attempt->status === 'in_progress' && $attempt->quiz_started_at !== null, 409);
        }

        $question = DB::table('questions')->where('test_code', $attempt->test_code)
            ->where('bank_version', $attempt->question_revision)
            ->where('position', $attempt->current_position)->first();
        abort_unless($question !== null, 500, 'Не найден вопрос.');

        $options = OptionPresentation::ordered(
            DB::table('answer_options')->where('question_id', $question->id)->orderBy('position')->get(),
            $attempt->option_shuffle_seed
        );
        $selected = DB::table('attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->value('answer_option_id');

        $elapsed = AttemptTimer::saved($attempt);
        if ($attempt->elapsed_seconds === null) {
            DB::table('quiz_attempts')->where('id', $attempt->id)->where('status', 'in_progress')
                ->whereNull('elapsed_seconds')->update(['elapsed_seconds' => $elapsed]);
            $elapsed = (int) DB::table('quiz_attempts')->where('id', $attempt->id)->value('elapsed_seconds');
        }
        $request->session()->put('quiz_timer_view', ['uuid' => $attempt->uuid, 'at' => now()->getTimestamp()]);

        return view('quiz', [
            'attempt' => $attempt,
            'question' => $question,
            'cells' => json_decode($question->cells, true, 512, JSON_THROW_ON_ERROR),
            'options' => $options,
            'selected' => $selected,
            'totalQuestions' => (int) $attempt->question_count,
            'answeredQuestions' => DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count(),
            'elapsedSeconds' => $elapsed,
            'timerMaxSeconds' => AttemptTimer::MAX_SECONDS,
            'timerIdleSeconds' => AttemptTimer::IDLE_SECONDS,
        ]);
    }

    public function answer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'navigation' => 'required|in:back,next,finish',
            'answer_option_id' => 'nullable|integer|min:1',
            'question_id' => 'sometimes|required|integer|min:1',
            'attempt_uuid' => 'sometimes|required|uuid',
        ]);

        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', $attempt->uuid);
        }
        if ($attempt->status === 'expired') {
            $redirect = $this->start($request, $attempt->uuid);
            if ($request->session()->get('active_attempt') !== $attempt->uuid) {
                return $redirect;
            }
            $attempt = $this->attempt($request, $attempt->uuid);
        }
        if ((int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
            return $this->start($request);
        }
        if (isset($validated['attempt_uuid']) && $validated['attempt_uuid'] !== $attempt->uuid) {
            abort(409, 'Попытка уже изменилась. Обновите тест.');
        }

        $this->assertCurrentQuestionBank();

        if ($attempt->gender === null || $attempt->age_group === null || $attempt->quiz_started_at === null) {
            return redirect()->route('quiz.show');
        }

        $answeredBefore = DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count();

        $view = $request->session()->get('quiz_timer_view');
        $reported = AttemptTimer::reported($request->input('timer_elapsed'));
        $completed = DB::transaction(function () use ($attempt, $validated, $view, $reported, $request): bool {
            $locked = DB::table('quiz_attempts')->where('id', $attempt->id)->lockForUpdate()->first();
            abort_unless($locked !== null, 404);
            $owned = $request->user()
                ? (int) $locked->user_id === (int) $request->user()->id
                : $locked->user_id === null && in_array($locked->uuid, $request->session()->get('owned_attempts', []), true);
            abort_unless($owned, 404);
            if ($locked->status === 'completed') {
                return true;
            }
            abort_unless($locked->status === 'in_progress', 409);

            $questionCount = (int) $locked->question_count;
            abort_unless($questionCount > 0
                && (string) $locked->test_code === PuzzleCatalog::TEST_CODE
                && (int) $locked->question_revision === PuzzleCatalog::VERSION
                && $questionCount === DB::table('questions')
                    ->where('test_code', $locked->test_code)
                    ->where('bank_version', $locked->question_revision)->count(), 409,
                'Состав вопросов изменился. Завершение теста приостановлено.');
            if ($locked->bank_fingerprint !== null) {
                abort_unless(QuestionBankIntegrity::matches($locked), 409,
                    'Опубликованный набор вопросов изменился. Ответы временно не принимаются.');
            }

            $question = DB::table('questions')->where('test_code', $locked->test_code)
                ->where('bank_version', $locked->question_revision)
                ->where('position', $locked->current_position)->first();
            abort_unless($question !== null, 500);
            // Reject stale submissions from another tab or a slow network response.
            if (isset($validated['question_id']) && (int) $validated['question_id'] !== (int) $question->id) {
                abort(409, 'Вопрос уже изменился. Обновите тест.');
            }

            $seconds = AttemptTimer::checkpoint($locked, $reported, $view, AttemptTimer::saved($locked));

            if (isset($validated['answer_option_id'])) {
                $option = DB::table('answer_options')
                    ->where('id', $validated['answer_option_id'])
                    ->where('question_id', $question->id)->first();

                if ($option === null) {
                    throw ValidationException::withMessages([
                        'answer_option_id' => 'Выберите один из вариантов этого вопроса.',
                    ]);
                }

                DB::table('attempt_answers')->upsert([[
                    'attempt_id' => $locked->id,
                    'question_id' => $question->id,
                    'answer_option_id' => $option->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]], ['attempt_id', 'question_id'], ['answer_option_id', 'updated_at']);
            }

            if ($validated['navigation'] === 'back') {
                DB::table('quiz_attempts')->where('id', $locked->id)->update([
                    'current_position' => max(1, (int) $locked->current_position - 1),
                    'elapsed_seconds' => $seconds,
                    'updated_at' => now(),
                ]);

                return false;
            }

            $hasAnswer = DB::table('attempt_answers')
                ->where('attempt_id', $locked->id)
                ->where('question_id', $question->id)->exists();

            if (! $hasAnswer) {
                throw ValidationException::withMessages([
                    'answer_option_id' => 'Выберите вариант ответа перед продолжением.',
                ]);
            }

            if ($validated['navigation'] === 'next') {
                DB::table('quiz_attempts')->where('id', $locked->id)->update([
                    'current_position' => min($questionCount, (int) $locked->current_position + 1),
                    'elapsed_seconds' => $seconds,
                    'updated_at' => now(),
                ]);

                return false;
            }

            if ((int) $locked->current_position !== $questionCount) {
                abort(409, 'Нельзя закончить тест до последнего вопроса.');
            }

            $correct = AttemptScoring::grade($locked);

            DB::table('quiz_attempts')->where('id', $locked->id)
                ->where('status', 'in_progress')->update([
                    'status' => 'completed',
                    'correct_count' => $correct,
                    'elapsed_seconds' => $seconds,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        }, 3);

        if (! $completed) {
            $this->flashProgressMilestone($request, $attempt, $answeredBefore);
        }

        return $completed
            ? redirect()->route('paywall', $attempt->uuid)
            : redirect()->route('quiz.show');
    }

    public function paywall(Request $request, string $uuid): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }
        if ($this->hasReportAccess($attempt->id)) {
            return redirect()->route('result', $uuid);
        }

        return view('paywall', compact('attempt'));
    }

    public function checkout(Request $request, string $uuid): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }
        if ($this->hasReportAccess($attempt->id)) {
            return redirect()->route('result', $uuid);
        }

        return view('checkout', compact('attempt'));
    }

    public function confirm(Request $request, string $uuid): RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }

        DemoSubscriptionLifecycle::confirm($request, $attempt);

        return redirect()->route('result', $uuid);
    }

    public function result(Request $request, string $uuid): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }

        if (! $this->hasReportAccess($attempt->id)) {
            return redirect()->route('paywall', $uuid);
        }

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
            ->join('answer_options as correct', function ($join): void {
                $join->on('correct.question_id', '=', 'q.id')->where('correct.is_correct', '=', true);
            })
            ->where('a.attempt_id', $attempt->id)
            ->orderBy('q.position')
            ->select([
                'q.id as question_id', 'q.position', 'q.kind', 'q.prompt', 'q.cells', 'q.explanation', 'q.bank_version',
                'selected.id as selected_option_id', 'selected.graphic_value as selected_value', 'selected.code as selected_code',
                'correct.id as correct_option_id', 'correct.graphic_value as correct_value', 'correct.code as correct_code',
            ])->get();

        // The answer letters in the report must reproduce exactly what the
        // user saw, including a per-attempt order and historical fixed labels.
        if ($answers->isNotEmpty() && $attempt->option_shuffle_seed !== null) {
            $optionsByQuestion = DB::table('answer_options')
                ->whereIn('question_id', $answers->pluck('question_id')->all())
                ->get(['id', 'question_id', 'position', 'code'])
                ->groupBy('question_id');

            foreach ($answers as $answer) {
                $labels = OptionPresentation::labels(
                    $optionsByQuestion->get($answer->question_id, collect()),
                    $attempt->option_shuffle_seed
                );
                $answer->selected_code = $labels[(int) $answer->selected_option_id] ?? null;
                $answer->correct_code = $labels[(int) $answer->correct_option_id] ?? null;
            }
        }

        // Never use the current catalog's size to grade historical results.
        // Pre-snapshot attempts use their archived revision only, when available.
        $archivedCount = DB::table('questions')
            ->where('test_code', $attempt->test_code)
            ->where('bank_version', $attempt->question_revision)->count();
        $totalQuestions = (int) ($attempt->question_count ?: $archivedCount);
        $totalQuestions = $totalQuestions > 0 ? $totalQuestions : null;
        $storedCorrect = (int) $attempt->correct_count;
        $percentage = $totalQuestions !== null && $storedCorrect >= 0 && $storedCorrect <= $totalQuestions
            ? (int) round($storedCorrect * 100 / $totalQuestions) : null;

        $bankMatchesSnapshot = $attempt->bank_fingerprint === null
            || ($archivedCount > 0 && QuestionBankIntegrity::matches($attempt));

        // The current renderer is validated only for the two frozen visual
        // banks. Older revisions keep their numerical result, not a guessed image.
        $renderableRevision = in_array((int) $attempt->question_revision,
            [PuzzleCatalog::VERSION, PuzzleCatalogV7::VERSION, PuzzleCatalogV6::VERSION, PuzzleCatalogV5::VERSION], true);

        $validReview = $bankMatchesSnapshot && $renderableRevision
            && $totalQuestions !== null
            && $answers->count() === $totalQuestions
            && $answers->unique('position')->count() === $totalQuestions
            && $answers->every(static fn ($answer): bool => is_string($answer->selected_code)
                && $answer->selected_code !== ''
                && is_string($answer->correct_code)
                && $answer->correct_code !== '')
            && DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count() === $totalQuestions
            && $answers->filter(fn ($answer): bool => $answer->selected_code === $answer->correct_code)->count() === $storedCorrect;

        $categoryBreakdown = ($validReview ? $answers : collect())
            ->groupBy(fn ($item): string => PuzzleCatalog::categoryFor($item->kind))
            ->map(function ($group, string $label): array {
                $total = $group->count();
                $correct = $group->filter(fn ($item): bool => $item->selected_code === $item->correct_code)->count();

                return [
                    'label' => $label,
                    'total' => $total,
                    'correct' => $correct,
                    'percentage' => $total > 0 ? (int) round($correct / $total * 100) : 0,
                ];
            })->values()->all();

        $detailStatus = ! $bankMatchesSnapshot
            ? 'integrity'
            : ((int) $attempt->question_revision !== PuzzleCatalog::VERSION ? 'legacy' : 'incomplete');

        return view('result', [
            'attempt' => $attempt,
            'detailStatus' => $detailStatus,
            'categoryBreakdown' => $categoryBreakdown,
            // Archived graphical revisions keep their review when their saved
            // question and option rows remain complete and renderable.
            'answerReview' => $validReview && $answers->every(static fn ($answer): bool => PuzzleCatalog::find($answer->kind) !== null
                || in_array($answer->kind, ['dots', 'polygon', 'rotation', 'stripes', 'position'], true))
                ? $answers : collect(),
            'percentage' => $percentage,
            'totalQuestions' => $totalQuestions,
        ]);
    }

    /**
     * Only notify when a newly saved answer crosses a milestone. Revisions and
     * backward navigation must not replay notifications.
     */
    private function flashProgressMilestone(Request $request, object $attempt, int $answeredBefore): void
    {
        $total = DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)
            ->where('bank_version', PuzzleCatalog::VERSION)->count();
        if ($total < 3) {
            return;
        }

        $answeredNow = DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count();
        if ($answeredNow <= $answeredBefore || $answeredNow >= $total) {
            return;
        }

        $thresholds = [
            1 => (int) ceil($total / 3),
            2 => (int) ceil($total * 2 / 3),
        ];

        foreach ($thresholds as $stage => $threshold) {
            if ($answeredBefore < $threshold && $answeredNow >= $threshold) {
                $request->session()->flash('quiz_progress_milestone', [
                    'attempt_uuid' => $attempt->uuid,
                    'stage' => $stage,
                    'percent' => (int) round($answeredNow * 100 / $total),
                ]);

                break;
            }
        }
    }

    private function assertCurrentQuestionBank(): void
    {
        abort_unless(
            DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)
                ->where('bank_version', PuzzleCatalog::VERSION)->count() === PuzzleCatalog::COUNT,
            503,
            'Банк вопросов не соответствует опубликованной ревизии.'
        );
    }

    private function hasReportAccess(int $attemptId): bool
    {
        return DB::table('demo_payments')
            ->where('attempt_id', $attemptId)
            ->where('status', 'succeeded')
            ->exists();
    }

    private function attempt(Request $request, mixed $uuid): object
    {
        if (! is_string($uuid)) {
            throw new NotFoundHttpException;
        }
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)
            ->where('test_code', PuzzleCatalog::TEST_CODE)->first();
        if ($attempt === null) {
            throw new NotFoundHttpException;
        }
        if ($request->user()) {
            abort_unless((int) $attempt->user_id === (int) $request->user()->id, 404);
        } else {
            abort_unless($attempt->user_id === null
                && in_array($uuid, $request->session()->get('owned_attempts', []), true), 404);
        }

        return $attempt;
    }
}
