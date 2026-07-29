<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\BudgetEntry;
use App\Models\Goat;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BreedSeeder::class,
            SuperAdminSeeder::class,
            SiteSettingSeeder::class,
            SiteServiceSeeder::class,
        ]);

        // Default admin / farmer account (only if it doesn't already exist)
        User::firstOrCreate(
            ['email' => 'admin@goatfarm.test'],
            [
                'name'     => 'Farm Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Only seed sample data if no investors exist yet
        if (Investor::count() > 0) {
            return;
        }

        // --- INVESTORS (matching the Google Sheet) ---
        $abdulBasit = Investor::create([
            'name' => 'Abdul Basit', 'role' => 'investor', 'expense_split_pct' => 33.33,
            'email' => 'abdul.basit@example.com', 'phone' => '+92-300-1111111',
        ]);
        $mehboob = Investor::create([
            'name' => 'Mehboob Khan', 'role' => 'manager', 'expense_split_pct' => 33.33,
            'email' => 'mehboob.khan@example.com', 'phone' => '+92-300-2222222',
            'notes' => 'Manager — holds all funds and pays expenses',
        ]);
        $amir = Investor::create([
            'name' => 'Amir Ghafoor', 'role' => 'investor', 'expense_split_pct' => 33.34,
            'email' => 'amir.ghafoor@example.com', 'phone' => '+92-300-3333333',
        ]);

        // --- CAPITAL INVESTMENTS (PKR 200,000 each) ---
        foreach ([$abdulBasit, $mehboob, $amir] as $inv) {
            Investment::create([
                'investor_id' => $inv->id,
                'amount'      => 200000,
                'date'        => now()->subMonths(6),
                'notes'       => 'Initial capital contribution',
            ]);
        }

        // --- BREEDS ---
        $boer   = Breed::where('name', 'Boer')->first();
        $beetal = Breed::where('name', 'Beetal')->first();
        $sirohi = Breed::where('name', 'Sirohi')->first();
        $kamori = Breed::where('name', 'Kamori')->first();

        // --- LIVESTOCK (10 goats matching the sheet's sample data) ---
        // 3 Boer (in herd), 1 Beetal (in herd), 1 Sirohi (in herd), 1 Kamori (in herd)
        // 3 sold, 1 deceased = 10 total purchased, 6 in herd, 3 sold, 1 died

        $goatData = [
            ['tag_id' => 'G-001', 'breed' => $boer,   'gender' => 'male',   'status' => 'breeder',  'purchase_price' => 180, 'estimated_value' => 280, 'purchase_date' => '2026-01-15', 'vendor' => 'Pak Livestock Market', 'purchase_weight' => 25],
            ['tag_id' => 'G-002', 'breed' => $boer,   'gender' => 'female', 'status' => 'breeder',  'purchase_price' => 200, 'estimated_value' => 260, 'purchase_date' => '2026-01-15', 'vendor' => 'Pak Livestock Market', 'purchase_weight' => 22],
            ['tag_id' => 'G-003', 'breed' => $boer,   'gender' => 'male',   'status' => 'grower',   'purchase_price' => 160, 'estimated_value' => 220, 'purchase_date' => '2026-02-01', 'vendor' => 'Sindh Goat Farm', 'purchase_weight' => 18],
            ['tag_id' => 'G-004', 'breed' => $beetal, 'gender' => 'female', 'status' => 'breeder',  'purchase_price' => 150, 'estimated_value' => 250, 'purchase_date' => '2026-01-20', 'vendor' => 'Punjab Livestock', 'purchase_weight' => 20],
            ['tag_id' => 'G-005', 'breed' => $sirohi, 'gender' => 'male',   'status' => 'grower',   'purchase_price' => 130, 'estimated_value' => 220, 'purchase_date' => '2026-02-10', 'vendor' => 'Rajasthan Trading', 'purchase_weight' => 16],
            ['tag_id' => 'G-006', 'breed' => $kamori, 'gender' => 'female', 'status' => 'breeder',  'purchase_price' => 200, 'estimated_value' => 310, 'purchase_date' => '2026-01-25', 'vendor' => 'Sindh Premium Goats', 'purchase_weight' => 24],
            ['tag_id' => 'G-007', 'breed' => $boer,   'gender' => 'male',   'status' => 'sold',     'purchase_price' => 170, 'sale_price' => 350, 'estimated_value' => 0, 'purchase_date' => '2026-01-15', 'sale_date' => '2026-04-01', 'vendor' => 'Pak Livestock Market', 'purchase_weight' => 23, 'sale_weight' => 40],
            ['tag_id' => 'G-008', 'breed' => $beetal, 'gender' => 'female', 'status' => 'sold',     'purchase_price' => 140, 'sale_price' => 300, 'estimated_value' => 0, 'purchase_date' => '2026-02-01', 'sale_date' => '2026-05-15', 'vendor' => 'Punjab Livestock', 'purchase_weight' => 19, 'sale_weight' => 35],
            ['tag_id' => 'G-009', 'breed' => $sirohi, 'gender' => 'male',   'status' => 'sold',     'purchase_price' => 120, 'sale_price' => 280, 'estimated_value' => 0, 'purchase_date' => '2026-02-10', 'sale_date' => '2026-05-20', 'vendor' => 'Rajasthan Trading', 'purchase_weight' => 15, 'sale_weight' => 28],
            ['tag_id' => 'G-010', 'breed' => $kamori, 'gender' => 'male',   'status' => 'deceased', 'purchase_price' => 185, 'estimated_value' => 0, 'purchase_date' => '2026-03-01', 'vendor' => 'Sindh Premium Goats', 'purchase_weight' => 21, 'notes' => 'Died of illness'],
        ];

        foreach ($goatData as $data) {
            $breed = $data['breed'];
            unset($data['breed']);
            $data['breed_id'] = $breed->id;
            Goat::create($data);
        }

        // --- TRANSACTIONS / EXPENSES (matching the sheet) ---
        // Total expenses: PKR 1,150 across categories
        // The Manager (Mehboob) pays most expenses

        $expenseData = [
            ['category' => 'Feed & Fodder',            'amount' => 120, 'investor' => $mehboob, 'date' => '2026-04-01', 'payment_method' => 'cash', 'notes' => 'Monthly feed supply', 'is_recurring' => true],
            ['category' => 'Feed & Fodder',            'amount' => 100, 'investor' => $mehboob, 'date' => '2026-05-01', 'payment_method' => 'cash', 'notes' => 'Monthly feed supply', 'is_recurring' => true],
            ['category' => 'Feed & Fodder',            'amount' => 90,  'investor' => $mehboob, 'date' => '2026-06-01', 'payment_method' => 'cash', 'notes' => 'Monthly feed supply', 'is_recurring' => true],
            ['category' => 'Feed & Fodder',            'amount' => 100, 'investor' => $mehboob, 'date' => '2026-06-15', 'payment_method' => 'cash', 'notes' => 'Supplemental fodder'],
            ['category' => 'Veterinary & Medicine',    'amount' => 65,  'investor' => $mehboob, 'date' => '2026-04-10', 'payment_method' => 'cash', 'notes' => 'Vaccination round'],
            ['category' => 'Veterinary & Medicine',    'amount' => 40,  'investor' => $mehboob, 'date' => '2026-05-20', 'payment_method' => 'cash', 'notes' => 'Treatment for G-010'],
            ['category' => 'Veterinary & Medicine',    'amount' => 30,  'investor' => $mehboob, 'date' => '2026-06-05', 'payment_method' => 'cash', 'notes' => 'Deworming'],
            ['category' => 'Labor / Wages',            'amount' => 150, 'investor' => $abdulBasit, 'date' => '2026-04-01', 'payment_method' => 'bank_transfer', 'notes' => 'Farmhand wages (2 months)', 'is_recurring' => true],
            ['category' => 'Labor / Wages',            'amount' => 150, 'investor' => $abdulBasit, 'date' => '2026-06-01', 'payment_method' => 'bank_transfer', 'notes' => 'Farmhand wages (2 months)', 'is_recurring' => true],
            ['category' => 'Shelter & Maintenance',    'amount' => 120, 'investor' => $amir, 'date' => '2026-05-01', 'payment_method' => 'cash', 'notes' => 'Pen repair & roofing'],
            ['category' => 'Transport',                'amount' => 60,  'investor' => $mehboob, 'date' => '2026-05-15', 'payment_method' => 'cash', 'notes' => 'Goat transport to vet'],
            ['category' => 'Breeding Costs',           'amount' => 70,  'investor' => $mehboob, 'date' => '2026-06-01', 'payment_method' => 'cash', 'notes' => 'Stud service fee'],
            ['category' => 'Utilities',                'amount' => 55,  'investor' => $amir, 'date' => '2026-06-15', 'payment_method' => 'bank_transfer', 'notes' => 'Water & electricity (3 months)', 'is_recurring' => true],
        ];

        foreach ($expenseData as $exp) {
            $investor = $exp['investor'];
            unset($exp['investor']);
            $exp['type'] = 'expense';
            $exp['investor_id'] = $investor->id;
            Transaction::create($exp);
        }

        // --- INCOME (Goat Sales) ---
        $incomeData = [
            ['category' => 'Goat Sales', 'amount' => 350, 'investor' => $mehboob, 'date' => '2026-04-01', 'notes' => 'Sale of G-007 (Boer)', 'goat_tag' => 'G-007'],
            ['category' => 'Goat Sales', 'amount' => 300, 'investor' => $mehboob, 'date' => '2026-05-15', 'notes' => 'Sale of G-008 (Beetal)', 'goat_tag' => 'G-008'],
            ['category' => 'Goat Sales', 'amount' => 280, 'investor' => $mehboob, 'date' => '2026-05-20', 'notes' => 'Sale of G-009 (Sirohi)', 'goat_tag' => 'G-009'],
        ];

        foreach ($incomeData as $inc) {
            $investor = $inc['investor'];
            unset($inc['investor']);
            $inc['type'] = 'income';
            $inc['investor_id'] = $investor->id;
            $goat = Goat::where('tag_id', $inc['goat_tag'])->first();
            if ($goat) {
                $inc['goat_id'] = $goat->id;
            }
            unset($inc['goat_tag']);
            Transaction::create($inc);
        }

        // --- BUDGET ENTRIES (seed default categories) ---
        $year = (int) date('Y');
        foreach (BudgetEntry::INCOME_CATEGORIES as $cat) {
            BudgetEntry::create(['type' => 'income', 'category' => $cat, 'year' => $year]);
        }
        foreach (BudgetEntry::EXPENSE_CATEGORIES as $cat) {
            BudgetEntry::create(['type' => 'expense', 'category' => $cat, 'year' => $year]);
        }
    }
}
