<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AnswerSelectionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['answer_option_id' => ['required', 'integer', 'min:1']]);
        $uuid = $request->session()->get('active_attempt');

        abort_unless(is_string($uuid)
            && in_array($uuid, $request->session()->get('owned_attempts', []), true), 404);

        DB::transaction(function () use ($uuid, $data): void {
            $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->lockForUpdate()->first();
            abort_unless($attempt !== null, 404);
            if (auth()->check()) {
                abort_unless((int) $attempt->user_id === (int) auth()->id(), 404);
            } else {
                abort_unless($attempt->user_id === null, 404);
            }
            abort_unless($attempt->status === 'in_progress', 409, 'Тест уже завершён.');

            $question = DB::table('questions')->where('position', $attempt->current_position)->first();
            abort_unless($question !== null, 500);

            $option = DB::table('answer_options')
                ->where('id', $data['answer_option_id'])
                ->where('question_id', $question->id)
                ->first();

            if ($option === null) {
                throw ValidationException::withMessages([
                    'answer_option_id' => 'Недопустимый вариант ответа.',
                ]);
            }

            DB::table('attempt_answers')->upsert([[
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_option_id' => $option->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['attempt_id', 'question_id'], ['answer_option_id', 'updated_at']);
        }, 3);

        return response()->json(['saved' => true]);
    }
}
