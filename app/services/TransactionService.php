<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            // idempoten: request ulang mengembalikan data yang sudah ada
            $existing = Transaction::with('items')
                ->where('client_uuid', $data['client_uuid'])->first();
            if ($existing) {
                return $existing;
            }

            // harga selalu dari database, bukan dari klien
            $menus = Menu::whereIn('id', collect($data['items'])->pluck('menu_id'))
                ->where('is_active', true)
                ->get()->keyBy('id');

            $lines = [];
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $menu = $menus->get($item['menu_id']);
                if (! $menu) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada menu yang tidak aktif atau tidak ditemukan.',
                    ]);
                }
                $lineTotal = $menu->price * $item['qty'];
                $subtotal += $lineTotal;
                $lines[] = [
                    'menu_id'            => $menu->id,
                    'menu_name_snapshot' => $menu->name,
                    'price_snapshot'     => $menu->price,
                    'qty'                => $item['qty'],
                    'note'               => $item['note'] ?? null,
                    'line_total'         => $lineTotal,
                ];
            }

            $discount = 0; // aturan diskon menyusul
            $total = $subtotal - $discount;

            if ($data['payment_method'] === 'cash') {
                $amountPaid = (int) $data['amount_paid'];
                if ($amountPaid < $total) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Uang yang diterima kurang dari total.',
                    ]);
                }
            } else {
                $amountPaid = $total;
            }

            $transaction = Transaction::create([
                'client_uuid'    => $data['client_uuid'],
                'invoice_no'     => $this->nextInvoiceNo(),
                'user_id'        => $user->id,
                'table_label'    => $data['table_label'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'payment_method' => $data['payment_method'],
                'amount_paid'    => $amountPaid,
                'change_amount'  => $amountPaid - $total,
                'status'         => 'paid',
                'paid_at'        => now(),
            ]);

            $transaction->items()->createMany($lines);

            return $transaction->load('items');
        });
    }

    private function nextInvoiceNo(): string
    {
        $prefix = 'INV-' . now('Asia/Jakarta')->format('Ymd') . '-';

        $last = Transaction::withTrashed()
            ->where('invoice_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}