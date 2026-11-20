<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_metadata_approved_h1_and_structured_faq_match_visible_content(): void
    {
        $url = rtrim(route('landing'), '/').'/';
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<title>/', $html));
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
        $this->assertSame(1, preg_match_all('/<link rel="canonical"/', $html));
        $this->assertStringContainsString(
            'Тест на IQ онлайн — графические матрицы и логика | IQ.Профиль',
            $html
        );
        $this->assertStringContainsString(
            '<h1 id="hero-title">Тест на IQ онлайн:<br><span>графические матрицы</span><br><span>и логические задачи</span>',
            $html
        );
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<meta property="og:title"', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.$url.'">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html);
        $this->assertStringNotContainsString('<meta name="robots" content="noindex', $html);
        $this->assertStringNotContainsString('без регистрации', mb_strtolower($html));
        $this->assertStringNotContainsString('class="eyebrow"', $html);

        $visible = strip_tags((string) preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $html));
        $this->assertDoesNotMatchRegularExpression(
            '/\b25\b/u',
            $visible,
            'Marketing must not promise a fixed number of questions.'
        );

        $this->assertSame(
            1,
            preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $found)
        );
        $schema = json_decode($found[1][0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame(['WebSite', 'WebPage', 'FAQPage'], array_column($schema['@graph'], '@type'));
        $this->assertSame($url, $schema['@graph'][1]['url']);
        $this->assertSame('ru-RU', $schema['@graph'][1]['inLanguage']);
        $this->assertSame(['@id' => $url.'#webpage'], $schema['@graph'][2]['isPartOf']);
        $this->assertSame($url.'#faq', $schema['@graph'][2]['url']);

        $faq = config('iq_faq');
        $mainEntity = $schema['@graph'][2]['mainEntity'];
        $this->assertCount(count($faq), $mainEntity);
        foreach ($faq as $index => $entry) {
            $this->assertSame('Question', $mainEntity[$index]['@type']);
            $this->assertSame($entry['question'], $mainEntity[$index]['name']);
            $this->assertSame('Answer', $mainEntity[$index]['acceptedAnswer']['@type']);
            $this->assertSame($entry['answer'], $mainEntity[$index]['acceptedAnswer']['text']);
            $this->assertStringContainsString('<summary>'.e($entry['question']).'</summary>', $html);
            $this->assertStringContainsString('<p>'.e($entry['answer']).'</p>', $html);
        }
    }

    public function test_faq_is_unique_factual_future_proof_and_consistent_with_public_copy(): void
    {
        $faq = config('iq_faq');
        $this->assertGreaterThanOrEqual(10, count($faq));
        $this->assertCount(count($faq), array_unique(array_column($faq, 'question')));
        $this->assertCount(count($faq), array_unique(array_column($faq, 'answer')));

        $content = implode(' ', array_merge(
            array_column($faq, 'question'),
            array_column($faq, 'answer')
        ));
        $this->assertStringNotContainsString('без регистрации', mb_strtolower($content));
        $this->assertDoesNotMatchRegularExpression('/\b25\b/u', $content);
        $this->assertStringContainsString('20 ₽', $content);
        $this->assertStringContainsString('499 ₽', $content);
        $this->assertStringContainsString('отменить', mb_strtolower($content));

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(count($faq), preg_match_all('/<details>\s*<summary>/s', $html));
        foreach ($faq as $item) {
            $this->assertGreaterThan(90, mb_strlen($item['answer']));
            $this->assertStringContainsString(e($item['question']), $html);
            $this->assertStringContainsString(e($item['answer']), $html);
        }
    }

    public function test_paywall_checkout_and_report_describe_only_current_demo_and_own_result(): void
    {
        $paywall = file_get_contents(resource_path('views/paywall.blade.php'));
        $checkout = file_get_contents(resource_path('views/checkout.blade.php'));
        $result = file_get_contents(resource_path('views/result.blade.php'));

        $this->assertStringContainsString('<strong>20 ₽</strong><del>499 ₽</del>', $paywall);
        $this->assertStringContainsString('реальные списания отсутствуют', $paywall);
        $this->assertStringContainsString('Открыть отчёт за 20 ₽', $paywall);
        $this->assertStringContainsString('Подтвердить без списания', $checkout);
        $this->assertStringContainsString('деньги не списываются', $checkout);
        $this->assertStringContainsString('Нормативный IQ и возрастные процентили не рассчитываются', $result);
        $this->assertStringContainsString('{{ $totalQuestions ?? ', $result);

        $files = [
            resource_path('views/landing.blade.php'),
            resource_path('views/result.blade.php'),
            resource_path('views/paywall.blade.php'),
            resource_path('views/checkout.blade.php'),
            resource_path('views/intake.blade.php'),
            resource_path('views/auth/register.blade.php'),
            resource_path('views/partials/site-footer.blade.php'),
        ];
        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertStringNotContainsString('без регистрации', mb_strtolower($source));
            // Age-group labels and vector coordinates are functional inputs, not promotional copy.
            if (! str_ends_with($file, 'intake.blade.php')) {
                $copy = preg_replace('/<svg\b[^>]*>.*?<\/svg>/s', '', $source);
                $this->assertDoesNotMatchRegularExpression('/\b25\b/u', $copy);
            }
        }
    }
}
