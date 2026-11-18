<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
    private const QUESTION_COUNT = 25;

    public function start(Request $request): RedirectResponse
    {
        abort_unless(DB::table('questions')->count() === self::QUESTION_COUNT, 503, 'Банк вопросов пока не подготовлен.');

        $uuid = (string) Str::uuid();
        DB::table('quiz_attempts')->insert([
            'uuid' => $uuid,
            'status' => 'in_progress',
            'current_position' => 1,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owned = $request->session()->get('owned_attempts', []);
        $owned[] = $uuid;
        $request->session()->put('owned_attempts', array_values(array_unique($owned)));
        $request->session()->put('active_attempt', $uuid);

        return redirect()->route('quiz.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));
        if ($attempt->status === 'completed') {
            return redirect()->route('paywall', $attempt->uuid);
        }

        $question = DB::table('questions')->where('position', $attempt->current_position)->first();
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
            'startedTimestamp' => CarbonImmutable::parse($attempt->started_at, 'UTC')->getTimestamp(),
            'serverTimestamp' => now()->getTimestamp(),
        ]);
    }

    public function answer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'navigation' => 'required|in:back,next,finish',
            'answer_option_id' => 'nullable|integer|min:1',
        ]);

        $attempt = $this->attempt($request, $request->session()->get('active_attempt'));

        $completed = DB::transaction(function () use ($attempt, $validated): bool {
            $locked = DB::table('quiz_attempts')->where('id', $attempt->id)->lockForUpdate()->first();
            if ($locked->status === 'completed') {
                return true;
            }

            $question = DB::table('questions')->where('position', $locked->current_position)->first();
            abort_unless($question !== null, 500);

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
                    'current_position' => min(self::QUESTION_COUNT, (int) $locked->current_position + 1),
                    'updated_at' => now(),
                ]);

                return false;
            }

            if ((int) $locked->current_position !== self::QUESTION_COUNT) {
                abort(409, 'Нельзя закончить тест до последнего вопроса.');
            }

            $count = DB::table('attempt_answers')->where('attempt_id', $locked->id)->count();
            if ($count !== self::QUESTION_COUNT) {
                throw ValidationException::withMessages([
                    'answer_option_id' => 'Для завершения ответьте на все 25 вопросов.',
                ]);
            }

            $correct = DB::table('attempt_answers as a')
                ->join('answer_options as o', 'o.id', '=', 'a.answer_option_id')
                ->where('a.attempt_id', $locked->id)
                ->where('o.is_correct', true)->count();

            $seconds = max(0, (int) abs(CarbonImmutable::parse($locked->started_at, 'UTC')->diffInSeconds(now())));

            DB::table('quiz_attempts')->where('id', $locked->id)->update([
                'status' => 'completed',
                'correct_count' => $correct,
                'elapsed_seconds' => $seconds,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        }, 3);

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

        return view('paywall', compact('attempt'));
    }

    public function checkout(Request $request, string $uuid): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }

        return view('checkout', compact('attempt'));
    }

    public function confirm(Request $request, string $uuid): RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }

        DB::transaction(function () use ($attempt): void {
            DB::table('quiz_attempts')->where('id', $attempt->id)->lockForUpdate()->first();

            DB::table('demo_payments')->insertOrIgnore([
                'attempt_id' => $attempt->id,
                'status' => 'succeeded',
                'amount_kopecks' => 2000,
                'currency' => 'RUB',
                'future_monthly_kopecks' => 49900,
                'confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return redirect()->route('result', $uuid);
    }

    public function result(Request $request, string $uuid): View|RedirectResponse
    {
        $attempt = $this->attempt($request, $uuid);
        if ($attempt->status !== 'completed') {
            return redirect()->route('quiz.show');
        }

        $paid = DB::table('demo_payments')
            ->where('attempt_id', $attempt->id)
            ->where('status', 'succeeded')->exists();

        if (! $paid) {
            return redirect()->route('paywall', $uuid);
        }

        $answers = DB::table('questions as q')
            ->leftJoin('attempt_answers as a', function ($join) use ($attempt): void {
                $join->on('a.question_id', '=', 'q.id')->where('a.attempt_id', '=', $attempt->id);
            })
            ->leftJoin('answer_options as selected', 'selected.id', '=', 'a.answer_option_id')
            ->leftJoin('answer_options as correct', function ($join): void {
                $join->on('correct.question_id', '=', 'q.id')->where('correct.is_correct', '=', true);
            })
            ->orderBy('q.position')
            ->select([
                'q.position', 'q.kind', 'q.explanation',
                'selected.graphic_value as selected_value', 'selected.code as selected_code',
                'correct.graphic_value as correct_value', 'correct.code as correct_code',
            ])->get();

        return view('result', [
            'attempt' => $attempt,
            'answers' => $answers,
            'percentage' => (int) round(($attempt->correct_count / self::QUESTION_COUNT) * 100),
        ]);
    }

    private function attempt(Request $request, mixed $uuid): object
    {
        if (! is_string($uuid) || ! in_array($uuid, $request->session()->get('owned_attempts', []), true)) {
            throw new NotFoundHttpException;
        }
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();
        if ($attempt === null) {
            throw new NotFoundHttpException;
        }

        return $attempt;
    }
}
