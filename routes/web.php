<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemoSubscriptionController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\YandexController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/offer', [LegalController::class, 'offer'])->name('legal.offer');
Route::get('/personal-data-consent', [LegalController::class, 'consent'])->name('legal.consent');
Route::get('/subscription/cancel', [DemoSubscriptionController::class, 'show'])->name('subscription.cancel');
Route::post('/subscription/cancel', [DemoSubscriptionController::class, 'cancel'])
    ->middleware('throttle:10,1')->name('subscription.cancel.perform');

Route::get('/robots.txt', function () {
    $blocked = [
        '/admin', '/account', '/auth/', '/login', '/register',
        '/forgot-password', '/reset-password', '/quiz', '/attempts',
        '/paywall/', '/checkout/', '/result/', '/subscription/cancel',
    ];
    $rules = implode("\n", array_map(fn (string $path): string => 'Disallow: '.$path, $blocked));
    $common = "Allow: /\n".$rules;
    $content = "User-agent: Yandex\n".$common
        ."\nClean-param: utm_source&utm_medium&utm_campaign&utm_content&utm_term&yclid&gclid\n\n"
        ."User-agent: Googlebot\n".$common."\n\n"
        ."User-agent: *\n".$common
        ."\n\nSitemap: ".route('sitemap')."\n";

    return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');

Route::get('/sitemap.xml', function () {
    // Legal templates still contain draft operator details; only the landing page is indexable.
    $routes = ['landing'];
    $urls = implode('', array_map(function (string $name): string {
        $url = $name === 'landing' ? rtrim(route($name), '/').'/' : route($name);

        return '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc></url>';
    }, $routes));
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/auth/yandex', [YandexController::class, 'redirect'])->name('auth.yandex.redirect');
    Route::get('/auth/yandex/callback', [YandexController::class, 'callback'])->name('auth.yandex.callback');
    Route::get('/auth/yandex/consent', [YandexController::class, 'consentForm'])->name('auth.yandex.consent');
    Route::post('/auth/yandex/consent', [YandexController::class, 'consentComplete'])
        ->middleware('throttle:5,1')->name('auth.yandex.consent.perform');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.perform');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.perform');
    Route::get('/forgot-password', [AuthController::class, 'resetRequestForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'resetRequest'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.home');
    Route::get('/account/export/{uuid}', [AccountController::class, 'export'])->name('account.export');
    Route::patch('/account/profile', [AuthController::class, 'profile'])->name('account.profile');
    Route::patch('/account/password', [AuthController::class, 'password'])->middleware('throttle:5,1')->name('account.password');
});
Route::middleware(['auth', EnsureAdministrator::class])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.home');
});

Route::post('/attempts', [QuizController::class, 'start'])->middleware('throttle:20,1')->name('attempt.start');
Route::get('/quiz', [QuizController::class, 'show'])->name('quiz.show');
Route::get('/quiz/intake/{step}', [QuizController::class, 'intake'])
    ->whereIn('step', ['gender', 'age'])->name('quiz.intake');
Route::post('/quiz/intake/{step}', [QuizController::class, 'saveIntake'])
    ->whereIn('step', ['gender', 'age'])->middleware('throttle:20,1')->name('quiz.intake.save');
Route::get('/attempts/{uuid}/resume', [QuizController::class, 'resume'])->name('attempt.resume');
Route::post('/quiz/answer', [QuizController::class, 'answer'])->name('quiz.answer');
Route::get('/paywall/{uuid}', [QuizController::class, 'paywall'])->name('paywall');
Route::get('/checkout/{uuid}', [QuizController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{uuid}/confirm', [QuizController::class, 'confirm'])->name('checkout.confirm');
Route::get('/result/{uuid}', [QuizController::class, 'result'])->name('result');
