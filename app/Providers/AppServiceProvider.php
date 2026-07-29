<?php

namespace App\Providers;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\BudgetEntry;
use App\Models\Caretaker;
use App\Models\Goat;
use App\Models\HealthRecord;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\OwnershipUnit;
use App\Models\ProfitDistribution;
use App\Models\SiteService;
use App\Models\SiteSetting;
use App\Models\Transaction;
use App\Observers\ActivityLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $trackableModels = [
            Goat::class,
            Breed::class,
            Investor::class,
            Transaction::class,
            HealthRecord::class,
            BreedingRecord::class,
            Investment::class,
            OwnershipUnit::class,
            BudgetEntry::class,
            Caretaker::class,
            ProfitDistribution::class,
            SiteSetting::class,
            SiteService::class,
        ];

        foreach ($trackableModels as $model) {
            $model::observe(ActivityLogObserver::class);
        }
    }
}
