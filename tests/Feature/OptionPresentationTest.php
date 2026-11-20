<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\OptionPresentation;
use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OptionPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    private function begin(): string
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified']);
        $this->post('/quiz/intake/age', ['age_group' => '25_34']);
        $this->get('/quiz')->assertOk();

        return (string) session('active_attempt');
    }

    public function test_answer_order_is_stable_after_navigation_reload_and_revised_selection(): void
    {
        $uuid = $this->begin();
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/D', $attempt->option_shuffle_seed);
        $firstPage = $this->get('/quiz')->assertOk();
        $options = $firstPage->viewData('options');
        $ids = $options->pluck('id')->all();
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'F'], $options->pluck('code')->all());

        $this->post('/quiz/answer', [
            'question_id' => $firstPage->viewData('question')->id,
            'answer_option_id' => $ids[1],
            'navigation' => 'next',
        ])->assertRedirect('/quiz');

        $this->post('/quiz/answer', ['navigation' => 'back'])->assertRedirect('/quiz');
        $restored = $this->get('/quiz')->assertOk();
        $this->assertSame($ids, $restored->viewData('options')->pluck('id')->all());
        $this->assertSame($ids[1], (int) $restored->viewData('selected'));

        $this->post('/quiz/answer', [
            'question_id' => $firstPage->viewData('question')->id,
            'answer_option_id' => $ids[4],
            'navigation' => 'next',
        ])->assertRedirect('/quiz');
        $this->post('/quiz/answer', ['navigation' => 'back'])->assertRedirect('/quiz');
        $this->assertSame($ids[4], (int) $this->get('/quiz')->viewData('selected'));
    }

    public function test_all_report_labels_match_the_user_visible_order_not_canonical_code(): void
    {
        $uuid = $this->begin();
        $chosenLabels = [];

        for ($position = 1; $position <= PuzzleCatalog::COUNT; $position++) {
            $view = $this->get('/quiz')->assertOk();
            $question = $view->viewData('question');
            $correctId = (int) DB::table('answer_options')
                ->where('question_id', $question->id)->where('is_correct', true)->value('id');

            $chosen = $view->viewData('options')->firstWhere('id', $correctId);
            $chosenLabels[$position] = $chosen->code;
            $this->post('/quiz/answer', [
                'question_id' => $question->id,
                'answer_option_id' => $correctId,
                'navigation' => $position === PuzzleCatalog::COUNT ? 'finish' : 'next',
            ])->assertRedirect($position === PuzzleCatalog::COUNT
                ? '/paywall/'.$uuid : '/quiz');
        }

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $report = $this->get('/result/'.$uuid)->assertOk();
        $this->assertSame(100, $report->viewData('percentage'));
        $this->assertCount(PuzzleCatalog::COUNT, $report->viewData('answerReview'));

        foreach ($report->viewData('answerReview') as $item) {
            $this->assertSame($chosenLabels[(int) $item->position], $item->selected_code);
            $this->assertSame($chosenLabels[(int) $item->position], $item->correct_code);
        }
    }

    public function test_two_attempts_receive_distinct_secret_ordering_seeds(): void
    {
        $firstUuid = $this->begin();
        $first = DB::table('quiz_attempts')->where('uuid', $firstUuid)->first();

        $this->withSession(['owned_attempts' => [], 'active_attempt' => null]);
        $secondUuid = $this->begin();
        $second = DB::table('quiz_attempts')->where('uuid', $secondUuid)->first();

        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertNotSame($first->option_shuffle_seed, $second->option_shuffle_seed);
    }

    public function test_existing_unshuffled_attempts_keep_original_answer_letters(): void
    {
        $uuid = $this->begin();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update(['option_shuffle_seed' => null]);

        $page = $this->get('/quiz')->assertOk();
        $options = $page->viewData('options');
        $original = DB::table('answer_options')
            ->where('question_id', $page->viewData('question')->id)
            ->orderBy('position')->get();

        $this->assertSame($original->pluck('id')->all(), $options->pluck('id')->all());
        $this->assertSame($original->pluck('code')->all(), $options->pluck('code')->all());
    }

    public function test_order_labels_are_stable_and_support_more_than_26_options(): void
    {
        $options = collect();
        for ($i = 1; $i <= 28; $i++) {
            $options->push((object) [
                'id' => $i, 'position' => $i, 'code' => 'A',
            ]);
        }

        $a = OptionPresentation::ordered($options, str_repeat('0', 32));
        $b = OptionPresentation::ordered($options, str_repeat('0', 32));
        $this->assertSame($a->pluck('id')->all(), $b->pluck('id')->all());
        $this->assertSame($a->pluck('code')->all(), $b->pluck('code')->all());
        $this->assertSame('AA', $a[26]->code);
        $this->assertSame('AB', $a[27]->code);
        $this->assertSame('A', $a[0]->code);
    }
}
