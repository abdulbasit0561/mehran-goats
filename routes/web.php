<?php

use App\Http\Controllers\BreedController;
use App\Http\Controllers\BreedingController;
use App\Http\Controllers\BreedingRecordController;
use App\Http\Controllers\BudgetEntryController;
use App\Http\Controllers\CaretakerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoatController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\InstructionsController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\OwnershipUnitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('landing');

Route::middleware('auth')->group(function () {
    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Goats (full resource)
    Route::delete('/goats/bulk', [GoatController::class, 'bulkDestroy'])->name('goats.bulk-destroy');
    Route::resource('goats', GoatController::class);

    // Caretakers (every Nth kid share)
    Route::resource('caretakers', CaretakerController::class);

    // Breeding & Pregnancy Dashboard
    Route::get('/breeding', [BreedingController::class, 'index'])->name('breeding.index');

    // Breeds (user-managed)
    Route::resource('breeds', BreedController::class)->except(['show']);

    // Nested health & breeding records under goats
    Route::resource('goats.health-records', HealthRecordController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->shallow();

    Route::resource('goats.breeding-records', BreedingRecordController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->shallow();

    // Breeding record lifecycle actions (pregnancy + kidding)
    Route::post('/breeding-records/{breeding_record}/mark-pregnant', [BreedingRecordController::class, 'markPregnant'])
        ->name('breeding-records.mark-pregnant');
    Route::post('/breeding-records/{breeding_record}/mark-open', [BreedingRecordController::class, 'markOpen'])
        ->name('breeding-records.mark-open');
    Route::get('/breeding-records/{breeding_record}/kidding', [BreedingRecordController::class, 'recordKidding'])
        ->name('breeding-records.kidding');
    Route::post('/breeding-records/{breeding_record}/kidding', [BreedingRecordController::class, 'storeKidding'])
        ->name('breeding-records.kidding.store');

    // Investors (full resource)
    Route::resource('investors', InvestorController::class);

    // Nested investments under investors
    Route::resource('investors.investments', InvestmentController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->shallow();

    // Ownership units
    Route::resource('investors.ownership', OwnershipUnitController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->names([
            'index'   => 'investors.ownership.index',
            'create'  => 'investors.ownership.create',
            'store'   => 'investors.ownership.store',
        ]);
    Route::delete('/ownership/{ownershipUnit}', [OwnershipUnitController::class, 'destroy'])
        ->name('ownership.destroy');

    // Transactions (full resource)
    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Budget Planner
    Route::get('/budget', [BudgetEntryController::class, 'index'])->name('budget.index');
    Route::get('/budget/create', [BudgetEntryController::class, 'create'])->name('budget.create');
    Route::post('/budget', [BudgetEntryController::class, 'store'])->name('budget.store');
    Route::patch('/budget/{budget_entry}', [BudgetEntryController::class, 'update'])->name('budget.update');
    Route::post('/budget/{budget_entry}/inline', [BudgetEntryController::class, 'inlineUpdate'])->name('budget.inline');
    Route::delete('/budget/{budget_entry}', [BudgetEntryController::class, 'destroy'])->name('budget.destroy');
    Route::post('/budget/seed', [BudgetEntryController::class, 'seedYear'])->name('budget.seed');

    // Instructions
    Route::get('/instructions', [InstructionsController::class, 'index'])->name('instructions');

    // Reports
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::post('/reports/distribute', [ReportController::class, 'distribute'])->name('reports.distribute');
    Route::post('/reports/distributions/{distribution}/mark-paid', [ReportController::class, 'markPaid'])->name('reports.distributions.mark-paid');
    Route::get('/reports/investors', [ReportController::class, 'investorIndex'])->name('reports.investors');
    Route::get('/reports/investor/{investor}', [ReportController::class, 'investorStatement'])->name('reports.investor');
    Route::get('/reports/investor/{investor}/csv', [ReportController::class, 'investorCsv'])->name('reports.investor.csv');
});

require __DIR__.'/auth.php';
