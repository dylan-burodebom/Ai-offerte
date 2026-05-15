<?php

use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteStatusController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware(['auth', 'verified'])->name('dashboard.stats');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('quotes/export', [QuoteController::class, 'export'])->name('quotes.export');
    Route::post('quotes/bulk', [QuoteController::class, 'bulkAction'])->name('quotes.bulk');
    Route::get('quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::get('quotes/{quote}/edit', [QuoteController::class, 'edit'])->name('quotes.edit');
    Route::patch('quotes/{quote}/meta', [QuoteController::class, 'updateMeta'])->name('quotes.meta');
    Route::patch('quotes/{quote}/sections/{section}', [QuoteController::class, 'updateSection'])->name('quotes.sections.update');
    Route::post('quotes/{quote}/sections/{section}/restore', [QuoteController::class, 'restoreSection'])->name('quotes.sections.restore');
    Route::post('quotes/{quote}/version', [QuoteController::class, 'createVersion'])->name('quotes.version');
    Route::post('quotes/draft', [QuoteController::class, 'storeDraft'])->name('quotes.draft');
    Route::patch('quotes/{quote}/draft', [QuoteController::class, 'updateDraft'])->name('quotes.draft.update');
    Route::post('quotes/{quote}/investments', [QuoteController::class, 'saveInvestments'])->name('quotes.investments');
    Route::patch('quotes/{quote}/status', [QuoteStatusController::class, 'update'])->name('quotes.status');
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
    Route::get('quotes/{quote}/generate', [QuoteController::class, 'generate'])->name('quotes.generate');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::get('quotes/{quote}/pdf/preview', [QuoteController::class, 'pdfPreview'])->name('quotes.pdf.preview');
    Route::post('clients/inline', [ClientController::class, 'storeInline'])->name('clients.store-inline');
    Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class)->except(['create', 'edit']);
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('prompts', [AdminPromptController::class, 'index'])->name('prompts');
    Route::patch('prompts/stijlgids', [AdminPromptController::class, 'updateStijlgids'])->name('prompts.stijlgids');
    Route::patch('prompts/sectoren/{sector}', [AdminPromptController::class, 'updateSector'])->name('prompts.sector');
    Route::post('prompts/preview', [AdminPromptController::class, 'preview'])->name('prompts.preview');
});

require __DIR__.'/auth.php';
