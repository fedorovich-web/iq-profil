<?php

declare(strict_types=1);

use App\Http\Controllers\AnswerSelectionController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n", 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});
Route::get('/sitemap.xml', function () {
    $homepage = htmlspecialchars(route('landing'), ENT_XML1 | ENT_COMPAT, 'UTF-8');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        .'<url><loc>'.$homepage.'</loc></url></urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
});
Route::post('/attempts', [QuizController::class, 'start'])->name('attempt.start');
Route::get('/quiz', [QuizController::class, 'show'])->name('quiz.show');
Route::post('/quiz/selection', AnswerSelectionController::class)->name('quiz.selection');
Route::post('/quiz/answer', [QuizController::class, 'answer'])->name('quiz.answer');
Route::get('/paywall/{uuid}', [QuizController::class, 'paywall'])->name('paywall');
Route::get('/checkout/{uuid}', [QuizController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{uuid}/confirm', [QuizController::class, 'confirm'])->name('checkout.confirm');
Route::get('/result/{uuid}', [QuizController::class, 'result'])->name('result');
