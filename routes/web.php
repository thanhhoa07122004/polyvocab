<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\Study\HistoryController;
use App\Http\Controllers\Study\LearnNewController;
use App\Http\Controllers\Study\PracticeController;
use App\Http\Controllers\Study\QuizController;
use App\Http\Controllers\Study\ReviewController;
use App\Http\Controllers\Study\StudySessionController;
use App\Http\Controllers\VocabularyEntryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Khám phá bộ từ công khai & xem chi tiết bộ từ, mục từ (cho khách và người đã đăng nhập)
Route::get('/explore', ExploreController::class)->name('explore');
Route::get('/decks/{deck}', [DeckController::class, 'show'])->name('decks.show')->whereNumber('deck');
Route::get('/decks/{deck}/entries/{entry}', [VocabularyEntryController::class, 'show'])
    ->name('decks.entries.show')
    ->whereNumber(['deck', 'entry']);

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Quản lý bộ từ của tôi (My Decks)
    Route::get('/decks', [DeckController::class, 'index'])->name('decks.index');
    Route::get('/my-decks', fn () => redirect()->route('decks.index'))->name('my-decks');
    Route::get('/decks/create', [DeckController::class, 'create'])->name('decks.create');
    Route::post('/decks', [DeckController::class, 'store'])->name('decks.store');
    Route::get('/decks/{deck}/edit', [DeckController::class, 'edit'])->name('decks.edit')->whereNumber('deck');
    Route::put('/decks/{deck}', [DeckController::class, 'update'])->name('decks.update')->whereNumber('deck');
    Route::delete('/decks/{deck}', [DeckController::class, 'destroy'])->name('decks.destroy')->whereNumber('deck');

    // Quản lý từ vựng trong bộ từ (Vocabulary Entries)
    Route::get('/decks/{deck}/entries/create', [VocabularyEntryController::class, 'create'])
        ->name('decks.entries.create')
        ->whereNumber('deck');
    Route::post('/decks/{deck}/entries', [VocabularyEntryController::class, 'store'])
        ->name('decks.entries.store')
        ->whereNumber('deck');
    Route::get('/decks/{deck}/entries/{entry}/edit', [VocabularyEntryController::class, 'edit'])
        ->name('decks.entries.edit')
        ->whereNumber(['deck', 'entry']);
    Route::put('/decks/{deck}/entries/{entry}', [VocabularyEntryController::class, 'update'])
        ->name('decks.entries.update')
        ->whereNumber(['deck', 'entry']);
    Route::delete('/decks/{deck}/entries/{entry}', [VocabularyEntryController::class, 'destroy'])
        ->name('decks.entries.destroy')
        ->whereNumber(['deck', 'entry']);

    // Chế độ Học tập & Luyện nhớ
    // 1. Học từ mới
    Route::get('/study/new', [LearnNewController::class, 'selectDeck'])->name('study.new.select');
    Route::get('/decks/{deck}/study/new', [LearnNewController::class, 'prepare'])
        ->name('study.new.prepare')
        ->whereNumber('deck');
    Route::post('/decks/{deck}/study/new/start', [LearnNewController::class, 'start'])
        ->name('study.new.start')
        ->whereNumber('deck');

    // 2. Ôn tập (Spaced Repetition)
    Route::get('/study/review', [ReviewController::class, 'index'])->name('study.review.index');
    Route::post('/study/review/start', [ReviewController::class, 'start'])->name('study.review.start');

    // 3. Luyện theo dạng bài & Luyện từ khó (Practice)
    Route::get('/study/practice', [PracticeController::class, 'index'])->name('study.practice.index');
    Route::get('/decks/{deck}/study/practice', [PracticeController::class, 'prepareDeck'])
        ->name('study.practice.deck')
        ->whereNumber('deck');
    Route::post('/study/practice/start', [PracticeController::class, 'start'])->name('study.practice.start');

    // 4. Kiểm tra (Quiz / Test)
    Route::get('/study/quiz', [QuizController::class, 'index'])->name('study.quiz.index');
    Route::post('/study/quiz/start', [QuizController::class, 'start'])->name('study.quiz.start');
    Route::get('/study/quiz/{session}', [QuizController::class, 'show'])
        ->name('study.quiz.show')
        ->whereNumber('session');
    Route::post('/study/quiz/{session}/save-answer', [QuizController::class, 'saveAnswer'])
        ->name('study.quiz.save_answer')
        ->whereNumber('session');
    Route::post('/study/quiz/{session}/submit', [QuizController::class, 'submit'])
        ->name('study.quiz.submit')
        ->whereNumber('session');
    Route::get('/study/quiz/{session}/result', [QuizController::class, 'result'])
        ->name('study.quiz.result')
        ->whereNumber('session');

    // 4. Lịch sử học tập cá nhân
    Route::get('/study/history', [HistoryController::class, 'index'])->name('study.history.index');

    // Trình chạy phiên học (Study Session Runner)
    Route::get('/study/sessions/{session}', [StudySessionController::class, 'show'])
        ->name('study.session.show')
        ->whereNumber('session');
    Route::post('/study/sessions/{session}/items/{item}/familiarize', [StudySessionController::class, 'familiarize'])
        ->name('study.session.familiarize')
        ->whereNumber(['session', 'item']);
    Route::post('/study/sessions/{session}/items/{item}/practice', [StudySessionController::class, 'practice'])
        ->name('study.session.practice')
        ->whereNumber(['session', 'item']);
    Route::post('/study/sessions/{session}/check-pair', [StudySessionController::class, 'checkPair'])
        ->name('study.session.check_pair')
        ->whereNumber('session');
    Route::post('/study/sessions/{session}/items/{item}/hint', [StudySessionController::class, 'hint'])
        ->name('study.session.hint')
        ->whereNumber(['session', 'item']);
    Route::post('/study/sessions/{session}/items/{item}/toggle-difficult', [StudySessionController::class, 'toggleDifficult'])
        ->name('study.session.toggle_difficult')
        ->whereNumber(['session', 'item']);
    Route::post('/study/sessions/{session}/items/{item}/quick-verify', [StudySessionController::class, 'quickVerify'])
        ->name('study.session.quick_verify')
        ->whereNumber(['session', 'item']);
    Route::post('/study/sessions/{session}/pause', [StudySessionController::class, 'pause'])
        ->name('study.session.pause')
        ->whereNumber('session');
    Route::post('/study/sessions/{session}/abandon', [LearnNewController::class, 'abandon'])
        ->name('study.session.abandon')
        ->whereNumber('session');
    Route::get('/study/sessions/{session}/complete', [StudySessionController::class, 'complete'])
        ->name('study.session.complete')
        ->whereNumber('session');

    // Khu vực quản trị hệ thống
    Route::middleware('can:access-admin')->prefix('admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('admin.dashboard');

        // Quản lý ngôn ngữ học
        Route::get('/languages', [AdminLanguageController::class, 'index'])->name('admin.languages.index');
        Route::get('/languages/create', [AdminLanguageController::class, 'create'])->name('admin.languages.create');
        Route::post('/languages', [AdminLanguageController::class, 'store'])->name('admin.languages.store');
        Route::get('/languages/{language}/edit', [AdminLanguageController::class, 'edit'])->name('admin.languages.edit')->whereNumber('language');
        Route::put('/languages/{language}', [AdminLanguageController::class, 'update'])->name('admin.languages.update')->whereNumber('language');
        Route::patch('/languages/{language}/toggle', [AdminLanguageController::class, 'toggleActive'])->name('admin.languages.toggle')->whereNumber('language');
    });
});
