<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DemoSubscriptionLifecycle;
use App\Support\PuzzleCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuizController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        abort_unless(DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->exists(), 503, 'Банк вопросов пока не подготовлен.');

        $attempt = DB::transaction(function () use ($request): object {
            // Two simultaneous clicks must not create two unpaid attempts.
            // A completed unpaid attempt takes precedence over a newer unfinished one.
            DemoSubscriptionLifecycle::lockOwner($request);
            $query = DB::table('quiz_attempts as a')
                ->leftJoin('demo_payments as p', function ($join): void {
                    $join->on('p.attempt_id', '=', 'a.id')->where('p.status', '=', 'succeeded');
                });

            if ($request->user()) {
                $query->where('a.user_id', $request->user()->id);
            } else {
                $query->whereNull('a.user_id')->whereIn('a.uuid', $request->session()->get('owned_attempts', []));
            }

            $blocked = $query->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('a.status', 'completed')->whereNull('p.id');
                })->orWhere(function ($query): void {
                    $query->where('a.status', 'in_progress')
                        ->where('a.question_revision', PuzzleCatalog::VERSION);
                });
            })->orderByRaw("CASE WHEN a.status = 'completed' THEN 0 ELSE 1 END")
                ->orderByDesc('a.id')
                ->select('a.uuid', 'a.status')->first();

            if ($blocked !== null) {
                return $blocked;
            }

            $uuid = (string) Str::uuid();
            DB::table('quiz_attempts')->insert([
                'uuid' => $uuid,
                'user_id' => $request->user()?->id,
                'status' => 'in_progress',
                'question_revision' => PuzzleCatalog::VERSION,
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
        if ($attempt->status !== 'completed' && (int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
            return $this->start($request);
        }
        $request->session()->put('active_attempt', $uuid);

        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', ['uuid' => $uuid]);
        }

        return redirect()->route('quiz.show');
    }

    public function intake(Request $request, string $step): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', $attempt->uuid);
        }
        if ((int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
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
        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status !== 'completed' && (int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
            return $this->start($request);
        }
        if ($attempt->status === 'completed') {
            return redirect()->route($this->hasReportAccess($attempt->id) ? 'result' : 'paywall', ['uuid' => $attempt->uuid]);
        }

        if ($attempt->gender === null) {
            return redirect()->route('quiz.intake', ['step' => 'gender']);
        }
        if ($attempt->age_group === null) {
            return redirect()->route('quiz.intake', ['step' => 'age']);
        }

        // A pre-test intake never contributes to elapsed_seconds.
        if ($attempt->quiz_started_at === null) {
            DB::table('quiz_attempts')->where('id', $attempt->id)
                ->whereNull('quiz_started_at')->update(['quiz_started_at' => now()]);
            $attempt->quiz_started_at = DB::table('quiz_attempts')
                ->where('id', $attempt->id)->value('quiz_started_at');
        }

        $question = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', $attempt->current_position)->first();
        abort_unless($question !== null, 500, 'Не найден вопрос.');

        $options = DB::table('answer_options')->where('question_id', $question->id)->orderBy('position')->get();
        $selected = DB::table('attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->value('answer_option_id');

        return view('quiz', [
            'attempt' => $attempt,
            'question' => $question,
            'cells' => json_decode($question->cells, true, 512, JSON_THROW_ON_ERROR),
            'options' => $options,
            'selected' => $selected,
            'totalQuestions' => DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->count(),
            'answeredQuestions' => DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count(),
            'startedTimestamp' => CarbonImmutable::parse($attempt->quiz_started_at, 'UTC')->getTimestamp(),
            'serverTimestamp' => now()->getTimestamp(),
        ]);
    }

    public function answer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'navigation' => 'required|in:back,next,finish',
            'answer_option_id' => 'nullable|integer|min:1',
            'question_id' => 'sometimes|required|integer|min:1',
        ]);

        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status !== 'completed' && (int) $attempt->question_revision !== PuzzleCatalog::VERSION) {
            return $this->start($request);
        }

        if ($attempt->gender === null || $attempt->age_group === null || $attempt->quiz_started_at === null) {
            return redirect()->route('quiz.show');
        }

        $answeredBefore = DB::table('attempt_answers')->where('attempt_id', $attempt->id)->count();

        $completed = DB::transaction(function () use ($attempt, $validated): bool {
            $questionCount = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->count();
            $locked = DB::table('quiz_attempts')->where('id', $attempt->id)->lockForUpdate()->first();
            if ($locked->status === 'completed') {
                return true;
            }

            $question = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
                ->where('position', $locked->current_position)->first();
            abort_unless($question !== null, 500);
            // Reject stale submissions from another tab or a slow network response.
            if (isset($validated['question_id']) && (int) $validated['question_id'] !== (int) $question->id) {
                abort(409, 'Вопрос уже изменился. Обновите тест.');
            }

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
                    'updated_at' => now(),
                ]);

                return false;
            }

            if ((int) $locked->current_position !== $questionCount) {
                abort(409, 'Нельзя закончить тест до последнего вопроса.');
            }

            $count = DB::table('attempt_answers')->where('attempt_id', $locked->id)->count();
            if ($count !== $questionCount) {
                throw ValidationException::withMessages([
                    'answer_option_id' => 'Для завершения ответьте на все вопросы.',
                ]);
            }

            $correct = DB::table('attempt_answers as a')
                ->join('answer_options as o', 'o.id', '=', 'a.answer_option_id')
                ->where('a.attempt_id', $locked->id)
                ->where('o.is_correct', true)->count();

            $seconds = max(0, (int) abs(CarbonImmutable::parse($locked->quiz_started_at, 'UTC')->diffInSeconds(now())));

            DB::table('quiz_attempts')->where('id', $locked->id)->update([
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
            ->join('questions as q', 'q.id', '=', 'a.question_id')
            ->join('answer_options as selected', 'selected.id', '=', 'a.answer_option_id')
            ->join('answer_options as correct', function ($join): void {
                $join->on('correct.question_id', '=', 'q.id')->where('correct.is_correct', '=', true);
            })
            ->where('a.attempt_id', $attempt->id)
            ->orderBy('q.position')
            ->select([
                'q.position', 'q.kind', 'q.prompt', 'q.cells', 'q.explanation', 'q.bank_version',
                'selected.graphic_value as selected_value', 'selected.code as selected_code',
                'correct.graphic_value as correct_value', 'correct.code as correct_code',
            ])->get();

        $totalQuestions = $answers->count() ?: PuzzleCatalog::COUNT;

        $categoryBreakdown = ((int) $attempt->question_revision >= 3
            ? $answers->where('bank_version', (int) $attempt->question_revision) : collect())
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

        return view('result', [
            'attempt' => $attempt,
            'categoryBreakdown' => $categoryBreakdown,
            'answerReview' => (int) $attempt->question_revision === PuzzleCatalog::VERSION ? $answers : collect(),
            'percentage' => (int) round(($attempt->correct_count / max(1, $totalQuestions)) * 100),
            'totalQuestions' => $totalQuestions,
        ]);
    }

    /**
     * Only notify when a newly saved answer crosses a milestone. Revisions and
     * backward navigation must not replay notifications.
     */
    private function flashProgressMilestone(Request $request, object $attempt, int $answeredBefore): void
    {
        $total = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->count();
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
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();
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
