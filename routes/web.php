<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgentChatController;
use App\Http\Controllers\BreedController;
use App\Http\Controllers\BreedingController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\BreedingRecordController;
use App\Http\Controllers\BudgetEntryController;
use App\Http\Controllers\CaretakerController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\GoatController;
use App\Http\Controllers\GoatImageController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\InstructionsController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\OwnershipUnitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\FarmAiInsightController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WeightLogController;
use App\Http\Controllers\YearlyClearanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    $settings = \App\Models\SiteSetting::allAsArray();
    $services = \App\Models\SiteService::active()->ordered()->get();
    $breeds = \App\Models\Breed::where('description', '!=', 'test')
        ->orWhereNull('description')
        ->orderBy('name')
        ->get(['id', 'name', 'description']);
    return view('landing', compact('breeds', 'settings', 'services'));
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
    Route::get('/goats/data', [GoatController::class, 'data'])->name('goats.data');
    Route::get('/goats/{goat}/declare-dead', [GoatController::class, 'declareDead'])->name('goats.declare-dead');
    Route::post('/goats/{goat}/declare-dead', [GoatController::class, 'storeDeath'])->name('goats.store-death');
    Route::resource('goats', GoatController::class);

    // Gallery images (nested under goats, shallow destroy)
    Route::post('/goats/{goat}/images', [GoatImageController::class, 'store'])->name('goats.images.store');
    Route::delete('/goats/images/{goat_image}', [GoatImageController::class, 'destroy'])->name('goats.images.destroy');

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

    // Weight history (nested store, shallow destroy)
    Route::post('/goats/{goat}/weight-logs', [WeightLogController::class, 'store'])->name('goats.weight-logs.store');
    Route::delete('/weight-logs/{weight_log}', [WeightLogController::class, 'destroy'])->name('goats.weight-logs.destroy');

    // Per-goat AI health insights (JSON only)
    Route::get('/goats/{goat}/ai-insights', [AiInsightController::class, 'index'])->name('goats.ai-insights.index');
    Route::post('/goats/{goat}/ai-insights', [AiInsightController::class, 'store'])->name('goats.ai-insights.store');
    Route::get('/goats/{goat}/ai-insights/latest', [AiInsightController::class, 'latest'])->name('goats.ai-insights.latest');

    // Farm-level AI insights (autonomous agent page)
    Route::get('/agent', [AgentChatController::class, 'index'])->name('agent.index');
    Route::post('/agent/messages', [AgentChatController::class, 'store'])->middleware('throttle:12,1')->name('agent.messages.store');
    Route::post('/agent/reset', [AgentChatController::class, 'reset'])->name('agent.reset');

    Route::get('/ai-insights', [FarmAiInsightController::class, 'index'])->name('farm.ai-insights.index');
    Route::post('/ai-insights/run', [FarmAiInsightController::class, 'run'])->name('farm.ai-insights.run');
    Route::get('/ai-insights/latest', [FarmAiInsightController::class, 'latest'])->name('farm.ai-insights.latest');

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

    // Quick-add investment (from investors list)
    Route::post('/investments/quick-add', [InvestmentController::class, 'quickStore'])
        ->name('investments.quick-add');

    // Customers / buyers (full resource)
    Route::get('/customers/data', [CustomerController::class, 'data'])->name('customers.data');
    Route::resource('customers', CustomerController::class);

    // Sales (full resource)
    Route::get('/sales/data', [SaleController::class, 'data'])->name('sales.data');
    Route::post('/sales/{sale}/payments', [SaleController::class, 'recordPayment'])->name('sales.payments.store');
    Route::delete('/sales/{sale}/payments/{payment}', [SaleController::class, 'deletePayment'])->name('sales.payments.destroy');
    Route::resource('sales', SaleController::class);

    // Investors (full resource)
    Route::get('/investors/data', [InvestorController::class, 'data'])->name('investors.data');
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
    Route::get('/transactions/data', [TransactionController::class, 'data'])->name('transactions.data');
    Route::delete('/transactions/bulk', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-destroy');
    Route::patch('/transactions/bulk', [TransactionController::class, 'bulkAssignInvestor'])->name('transactions.bulk-assign');
    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Exchanges (goat trading/barter)
    Route::get('/exchanges/data', [ExchangeController::class, 'data'])->name('exchanges.data');
    Route::resource('exchanges', ExchangeController::class);

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

    // CMS — Landing Page Content Management
    Route::get('/cms', [CmsController::class, 'index'])->name('cms.index');
    Route::post('/cms/branding', [CmsController::class, 'updateBranding'])->name('cms.update-branding');
    Route::post('/cms/general', [CmsController::class, 'updateGeneral'])->name('cms.update-general');
    Route::post('/cms/seo', [CmsController::class, 'updateSeo'])->name('cms.update-seo');
    Route::get('/cms/services/create', [CmsController::class, 'servicesCreate'])->name('cms.services.create');
    Route::post('/cms/services', [CmsController::class, 'servicesStore'])->name('cms.services.store');
    Route::get('/cms/services/{service}/edit', [CmsController::class, 'servicesEdit'])->name('cms.services.edit');
    Route::put('/cms/services/{service}', [CmsController::class, 'servicesUpdate'])->name('cms.services.update');
    Route::delete('/cms/services/{service}', [CmsController::class, 'servicesDestroy'])->name('cms.services.destroy');
    Route::post('/cms/services/{service}/toggle', [CmsController::class, 'servicesToggle'])->name('cms.services.toggle');

    // Offline-First Sync (JSON API, session-based auth)
    Route::get('/api/sync/pull', [SyncController::class, 'pull'])->name('sync.pull');
    Route::post('/api/sync/push', [SyncController::class, 'push'])->name('sync.push');

    // Reports
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/csv', [ReportController::class, 'exportSalesCsv'])->name('reports.sales.csv');
    Route::get('/reports/sales/pdf', [ReportController::class, 'exportSalesPdf'])->name('reports.sales.pdf');
    Route::get('/reports/profit-loss/csv', [ReportController::class, 'exportCsv'])->name('reports.profit-loss.csv');
    Route::get('/reports/profit-loss/pdf', [ReportController::class, 'exportPdf'])->name('reports.profit-loss.pdf');
    Route::post('/reports/distribute', [ReportController::class, 'distribute'])->name('reports.distribute');
    Route::post('/reports/distributions/{distribution}/mark-paid', [ReportController::class, 'markPaid'])->name('reports.distributions.mark-paid');
    Route::get('/reports/investors', [ReportController::class, 'investorIndex'])->name('reports.investors');
    Route::get('/reports/investor/{investor}', [ReportController::class, 'investorStatement'])->name('reports.investor');
    Route::get('/reports/investor/{investor}/csv', [ReportController::class, 'investorCsv'])->name('reports.investor.csv');

    // Yearly Clearance (annual settlement)
    Route::get('/reports/yearly-clearance', [YearlyClearanceController::class, 'index'])->name('reports.yearly-clearance.index');
    Route::get('/reports/yearly-clearance/report', [YearlyClearanceController::class, 'report'])->name('reports.yearly-clearance.report');
    Route::post('/reports/yearly-clearance/complete', [YearlyClearanceController::class, 'complete'])->name('reports.yearly-clearance.complete');
    Route::get('/reports/yearly-clearance/{clearance}', [YearlyClearanceController::class, 'show'])->name('reports.yearly-clearance.show');
    Route::get('/reports/yearly-clearance/{clearance}/csv', [YearlyClearanceController::class, 'csv'])->name('reports.yearly-clearance.csv');
    Route::get('/reports/yearly-clearance/{clearance}/pdf', [YearlyClearanceController::class, 'pdf'])->name('reports.yearly-clearance.pdf');

    // Activity Logs (super admin only)
    Route::middleware('super_admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/data', [ActivityLogController::class, 'data'])->name('activity-logs.data');
        Route::get('/activity-logs/{activity_log}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    });
});

require __DIR__.'/auth.php';
