<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Goat;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Central place that records a goat sale:
 *  - marks the goat sold (status/sale_price/sale_date/sale_weight)
 *  - creates/keeps exactly ONE "Goat Sales" income transaction, linked via sale_id
 *
 * All sale paths (Sales module, goat-edit mark-sold, Exchange) go through
 * here so income can never be double-counted.
 */
class SaleService
{
    /**
     * Record (or complete) a sale for a goat.
     *
     * Idempotent per goat: if a Sale already exists for the goat, the goat
     * fields and the linked income transaction are updated in place — no
     * second sale and no second income entry is ever created.
     *
     * @param array{customer_id?: int|null, seller_investor_id?: int|null, sale_date: string, sale_price: float, sale_weight?: float|null, payment_method?: string, photo_path?: string|null, notes?: string|null} $data
     */
    public function recordSale(Goat $goat, array $data): Sale
    {
        return DB::transaction(function () use ($goat, $data) {
            $price = (float) $data['sale_price'];
            $date  = $data['sale_date'];

            // 1. Mark the goat sold.
            $goat->update([
                'status'      => 'sold',
                'sale_price'  => $price,
                'sale_date'   => $date,
                'sale_weight' => $data['sale_weight'] ?? $goat->sale_weight ?? 0,
            ]);

            // 2. Find or create the Sale row (one per goat).
            $sale = $goat->sale()->withTrashed()->first();

            $attributes = [
                'customer_id'        => $data['customer_id'] ?? null,
                'seller_investor_id' => $data['seller_investor_id'] ?? null,
                'sale_date'          => $date,
                'sale_price'         => $price,
                'sale_weight'        => $data['sale_weight'] ?? null,
                'payment_method'     => $data['payment_method'] ?? 'cash',
                'notes'              => $data['notes'] ?? null,
            ];

            if ($sale) {
                // Editing an existing sale: preserve its payment history
                // (payments only get wiped in undoSale / fresh creation flow).
                if (! empty($data['photo_path'])) {
                    if ($sale->photo_path) {
                        Storage::disk('public')->delete($sale->photo_path);
                    }
                    $attributes['photo_path'] = $data['photo_path'];
                }
                $sale->update($attributes);
                $sale->restore();
                // Price may have changed — recompute paid/partial/paid status.
                $sale->refreshPaymentStatus();
            } else {
                $attributes['photo_path'] = $data['photo_path'] ?? null;
                $attributes['status'] = 'paid';
                $sale = Sale::create($attributes + ['goat_id' => $goat->id]);

                // Every newly created sale gets a "paid in full" payment row
                // so sale_payments data always agrees with the status column.
                // (The sales module overwrites this when the buyer pays less up front.)
                if ($price > 0) {
                    $sale->payments()->create([
                        'amount'         => $price,
                        'payment_date'   => $date,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'notes'          => 'Paid in full at sale',
                    ]);
                }
            }

            // 3. Exactly one "Goat Sales" income transaction, linked to the sale.
            if ($price > 0) {
                $transaction = Transaction::firstOrNew([
                    'sale_id' => $sale->id,
                    'type'    => 'income',
                    'category' => 'Goat Sales',
                ]);

                // Legacy guard: link an older unlinked tx for this goat if one exists.
                if (! $transaction->exists && $goat->transactions()
                        ->where('type', 'income')
                        ->where('category', 'Goat Sales')
                        ->whereNull('sale_id')
                        ->exists()) {
                    $transaction = $goat->transactions()
                        ->where('type', 'income')
                        ->where('category', 'Goat Sales')
                        ->whereNull('sale_id')
                        ->first();
                }

                $transaction->fill([
                    'amount'         => $price,
                    'date'           => $date,
                    'goat_id'        => $goat->id,
                    'investor_id'    => $data['seller_investor_id'] ?? null,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'notes'          => $data['notes'] ?? ('Sale of goat ' . $goat->tag_id),
                ]);
                $transaction->sale_id = $sale->id;
                $transaction->save();
            }

            return $sale;
        });
    }

    /**
     * Undo a sale: remove its income transaction, return the goat to the
     * active herd, and delete the sale (payments cascade with it).
     */
    public static function undoSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            Transaction::where('sale_id', $sale->id)->delete();

            // Hard-delete payments — a soft-deleted sale would resurrect them
            // if the goat is ever sold again.
            $sale->payments()->delete();

            $sale->goat?->update([
                'status'      => 'grower',
                'sale_price'  => 0,
                'sale_date'   => now()->toDateString(),
                'sale_weight' => 0,
            ]);

            $sale->forceDelete();
        });
    }
}
