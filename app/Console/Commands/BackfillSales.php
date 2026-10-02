<?php

namespace App\Console\Commands;

use App\Models\Goat;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Console\Command;

class BackfillSales extends Command
{
    protected $signature = 'sales:backfill';

    protected $description = 'Create Sale records for goats already marked sold (legacy data), linking existing Goat Sales income transactions without creating new income.';

    public function handle(): int
    {
        $sold = Goat::where('status', 'sold')->get();
        $created = 0;
        $linked = 0;

        foreach ($sold as $goat) {
            if (Sale::withTrashed()->where('goat_id', $goat->id)->exists()) {
                continue; // already has a sale — idempotent
            }

            $sale = Sale::create([
                'goat_id'        => $goat->id,
                'customer_id'    => null,
                'sale_date'      => $goat->sale_date ?? $goat->updated_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'sale_price'     => (float) ($goat->sale_price ?: 0),
                'sale_weight'    => $goat->sale_weight ?: null,
                'payment_method' => 'cash',
                'status'         => 'paid',
                'notes'          => 'Backfilled from goat record (sold before sales module)',
            ]);
            $created++;

            // Payment row so status=paid is consistent with sale_payments data.
            if ((float) ($goat->sale_price ?: 0) > 0) {
                $sale->payments()->create([
                    'amount'       => (float) $goat->sale_price,
                    'payment_date' => $goat->sale_date ?? $goat->updated_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                    'payment_method' => 'cash',
                    'notes'        => 'Paid in full (assumed at sale — backfilled)',
                ]);
            }

            // Link the existing income transaction (if any) — do NOT create one.
            $tx = Transaction::where('type', 'income')
                ->where('category', 'Goat Sales')
                ->whereNull('sale_id')
                ->where(function ($q) use ($goat) {
                    $q->where('goat_id', $goat->id);
                })
                ->first();

            if ($tx) {
                $tx->update(['sale_id' => $sale->id]);
                $linked++;
            }
        }

        $this->info("Backfill complete: {$created} sales created, {$linked} income transactions linked. No new income created.");

        return self::SUCCESS;
    }
}
