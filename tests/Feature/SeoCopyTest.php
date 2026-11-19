<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_has_independent_of_bank_size_seo_metadata_and_valid_json_ld(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<title>/', $html));
        $this->assertSame(1, preg_match_all('/<h1\\b/', $html));
        $this->assertSame(1, preg_match_all('/<link rel="canonical"/', $html));
        $this->assertStringContainsString('Тест на IQ онлайн без регистрации — задачи на логику', $html);
        $this->assertStringContainsString('<h1 id="hero-title">Тест на IQ онлайн<br><span>без регистрации</span>', $html);
        $this->assertStringContainsString('Какие задания', $html);
        $this->assertStringContainsString('Как пройти', $html);
        $this->assertStringContainsString('Частые вопросы', $html);
        $this->assertStringNotContainsString('Почему выбирают', $html);
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<meta property="og:title"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('landing').'">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('landing').'">', $html);
        $this->assertStringNotContainsString('<meta name="robots" content="noindex', $html);
        $this->assertStringNotContainsString('Предварительные ответы', $html);
        $this->assertStringNotContainsString('FAQPage', $html);
        $this->assertStringNotContainsString('Почему выбирают IQ.Профиль', $html);
        $this->assertSame(0, preg_match_all('/<h[1-3][^>]*>[^<]*IQ\\.Профиль/u', $html));

        $this->assertSame(1, preg_match_all('/<script type="application\\/ld\\+json">(.*?)<\\/script>/s', $html, $found));
        $schema = json_decode($found[1][0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame(['WebSite', 'WebPage'], array_column($schema['@graph'], '@type'));
        $this->assertSame(route('landing'), $schema['@graph'][1]['url']);
        $this->assertSame('ru-RU', $schema['@graph'][1]['inLanguage']);

        $this->assertDoesNotMatchRegularExpression('/\\b25\\b/u', $html);
    }

    public function test_faq_is_unique_grounded_and_not_tied_to_number_of_questions(): void
    {
        $faq = config('iq_faq');
        $this->assertCount(12, $faq);
        $this->assertCount(12, array_unique(array_column($faq, 'question')));
        $this->assertCount(12, array_unique(array_column($faq, 'answer')));

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(12, preg_match_all('/<details>\\s*<summary>/s', $html));

        foreach ($faq as $item) {
            $this->assertGreaterThan(90, mb_strlen($item['answer']));
            $this->assertStringContainsString(e($item['question']), $html);
            $this->assertStringContainsString(e($item['answer']), $html);
            $this->assertDoesNotMatchRegularExpression('/\\b25\\b/u', $item['question'].' '.$item['answer']);
        }
        foreach (['20 ₽', '499 ₽', 'без регистрации', 'психометрическую стандартизацию', 'отменить подписку'] as $phrase) {
            $this->assertStringContainsString($phrase, mb_strtolower($html));
        }
    }

    public function test_conversion_copy_preserves_planned_subscription_and_demo_disclosure(): void
    {
        $paywall = file_get_contents(resource_path('views/paywall.blade.php'));
        $checkout = file_get_contents(resource_path('views/checkout.blade.php'));
        $result = file_get_contents(resource_path('views/result.blade.php'));

        $this->assertStringContainsString('<strong>20 ₽</strong><del>499 ₽</del>', $paywall);
        $this->assertStringContainsString('499 ₽/месяц', $paywall);
        $this->assertStringContainsString('реальные списания отсутствуют', $paywall);
        $this->assertStringContainsString('Открыть отчёт за 20 ₽', $paywall);
        $this->assertStringContainsString('Подтвердить без списания', $checkout);
        $this->assertStringContainsString('499 ₽ в месяц', $checkout);
        $this->assertStringContainsString('деньги не списываются', $checkout);
        $this->assertStringContainsString('официальной оценкой IQ', $result);
        $this->assertStringContainsString('{{ $totalQuestions }}', $result);

        foreach ([$paywall, $checkout, $result] as $copy) {
            $this->assertDoesNotMatchRegularExpression('/\\b25\\b/u', $copy);
        }
    }
}
