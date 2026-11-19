<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>@yield('title', 'IQ.Профиль — тест на IQ онлайн')</title>
<meta name="description" content="@yield('description', 'Тест на IQ онлайн с графическими заданиями на логику и поиск закономерностей.')">
@hasSection('canonical-url')<link rel="canonical" href="@yield('canonical-url')">@endif
@hasSection('landing-nav')
<link rel="canonical" href="{{ route('landing') }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="IQ.Профиль">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="Тест на IQ онлайн без регистрации — задачи на логику">
<meta property="og:description" content="Пройти тест на IQ онлайн: логические матрицы, абстрактное мышление и задания с фигурами. Без регистрации, с персональным отчётом.">
<meta property="og:url" content="{{ route('landing') }}">
@if(file_exists(public_path('assets/iq_profile_hero_logic_matrix_desktop.avif')))
<meta property="og:image" content="{{ asset('assets/iq_profile_hero_logic_matrix_desktop.avif') }}">
<meta property="og:image:alt" content="Графическая матрица из фигур — IQ.Профиль">
@endif
<meta name="twitter:card" content="summary">
<script type="application/ld+json">{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'WebSite', '@id' => route('landing').'#website', 'name' => 'IQ.Профиль', 'url' => route('landing'), 'inLanguage' => 'ru-RU'],
        ['@type' => 'WebPage', '@id' => route('landing').'#webpage', 'url' => route('landing'),
            'name' => 'Тест на IQ онлайн без регистрации — задачи на логику',
            'description' => 'Тест на IQ онлайн с графическими матрицами, поиском закономерностей и персональным отчётом о прохождении.',
            'inLanguage' => 'ru-RU',
            'isPartOf' => ['@id' => route('landing').'#website']],
        [
            '@type' => 'FAQPage',
            '@id' => route('landing').'#faq',
            'url' => route('landing').'#faq',
            'inLanguage' => 'ru-RU',
            'isPartOf' => ['@id' => route('landing').'#webpage'],
            'mainEntity' => array_map(static fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], config('iq_faq')),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
@hasSection('private')<meta name="robots" content="noindex,nofollow,noarchive">@endif
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" sizes="any" href="{{ asset('favicon.ico') }}">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="@yield('page-class','page-default')">
<a class="skip-link" href="#main">Перейти к содержимому</a>
<div class="site-shell">
@include('partials.site-header')
<main id="main">@yield('content')</main>
</div>
@include('partials.site-footer')
</body>
</html>