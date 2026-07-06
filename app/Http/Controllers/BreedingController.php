<?php

namespace App\Http\Controllers;

use App\Models\BreedingRecord;
use App\Models\Goat;
use Illuminate\Http\Request;

class BreedingController extends Controller
{
    /**
     * Breeding & Pregnancy Dashboard.
     * Shows pregnant does, upcoming kiddings, birth rate stats, and recent kidding events.
     */
    public function index(Request $request)
    {
        // --- Pregnant does (bred or confirmed pregnant, not yet kidded) ---
        $pregnantRecords = BreedingRecord::with(['dam.breed', 'sire'])
            ->pregnant()
            ->orderBy('expected_kidding_date')
            ->get();

        // --- Upcoming kiddings (due within 30 days) ---
        $upcomingKiddings = BreedingRecord::with(['dam.breed', 'sire'])
            ->upcoming(30)
            ->orderBy('expected_kidding_date')
            ->get();

        // --- Overdue (past expected date, still pregnant) ---
        $overdueKiddings = BreedingRecord::with(['dam.breed'])
            ->overdue()
            ->orderBy('expected_kidding_date')
            ->get();

        // --- Recent kiddings (last 5 completed) ---
        $recentKiddings = BreedingRecord::with(['dam.breed', 'sire', 'kids'])
            ->kidded()
            ->latest('kidding_date')
            ->take(5)
            ->get();

        // --- Birth rate stats ---
        $stats = $this->calculateBirthStats();

        // --- Breeding females (does available for breeding) ---
        $breedingDoes = Goat::where('gender', 'female')
            ->whereNotIn('status', ['sold', 'deceased'])
            ->orderBy('tag_id')
            ->get();

        return view('breeding.index', compact(
            'pregnantRecords',
            'upcomingKiddings',
            'overdueKiddings',
            'recentKiddings',
            'stats',
            'breedingDoes',
        ));
    }

    /**
     * Calculate farm-wide birth rate statistics.
     */
    private function calculateBirthStats(): array
    {
        $kiddedRecords = BreedingRecord::where('pregnancy_status', 'kidded')->get();
        $totalBreedings = BreedingRecord::count();
        $totalKidsBorn = Goat::where('birth_type', 'farm_born')->count();
        $totalDoes = Goat::where('gender', 'female')
            ->whereNotIn('status', ['sold', 'deceased'])
            ->count();

        // Kidding rate: % of breedings that resulted in live birth
        $kiddingRate = $totalBreedings > 0
            ? round(($kiddedRecords->count() / $totalBreedings) * 100, 1)
            : 0;

        // Average litter size
        $littersWithKids = $kiddedRecords->where('litter_size', '>', 0);
        $avgLitterSize = $littersWithKids->count() > 0
            ? round($littersWithKids->avg('litter_size'), 1)
            : 0;

        // Births this year
        $birthsThisYear = Goat::where('birth_type', 'farm_born')
            ->whereYear('dob', now()->year)
            ->count();

        // Births this month
        $birthsThisMonth = Goat::where('birth_type', 'farm_born')
            ->whereYear('dob', now()->year)
            ->whereMonth('dob', now()->month)
            ->count();

        return [
            'total_breedings'      => $totalBreedings,
            'kidded_count'         => $kiddedRecords->count(),
            'kidding_rate'         => $kiddingRate,
            'total_kids_born'      => $totalKidsBorn,
            'avg_litter_size'      => $avgLitterSize,
            'births_this_year'     => $birthsThisYear,
            'births_this_month'    => $birthsThisMonth,
            'pregnant_count'       => BreedingRecord::pregnant()->count(),
            'breeding_does'        => $totalDoes,
        ];
    }
}
