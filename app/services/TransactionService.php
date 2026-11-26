<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Customer;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function void(User $user, Transaction $transaction, string $reason): Transaction
    {
        return DB::transaction(function () use ($user, $transaction, $reason) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($transaction->status !== 'paid' || $transaction->payment_method === 'debt') {
                throw ValidationException::withMessages(['reason' => 'Hanya transaksi lunas non-Bon yang dapat di-void.']);
            }

            $before = $transaction->only(['status', 'total', 'payment_method', 'paid_at']);
            $transaction->update(['status' => 'void', 'void_reason' => trim($reason)]);
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'transaction.voided',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'before_data' => $before,
                'after_data' => $transaction->only(['status', 'void_reason', 'total', 'payment_method', 'paid_at']),
            ]);

            return $transaction->load(['items', 'customer']);
        });
    }

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
            } elseif ($data['payment_method'] === 'debt') {
                $amountPaid = 0;
            } else {
                $amountPaid = $total;
            }

            $customer = null;
            if ($data['payment_method'] === 'debt') {
                $phone = trim($data['customer_phone'] ?? '');
                $customer = $phone
                    ? Customer::firstOrCreate(['phone' => $phone], ['name' => trim($data['customer_name'])])
                    : Customer::create(['name' => trim($data['customer_name'])]);
                if ($customer->wasRecentlyCreated === false && $customer->name !== trim($data['customer_name'])) {
                    $customer->update(['name' => trim($data['customer_name'])]);
                }
            }

            $transaction = Transaction::create([
                'client_uuid'    => $data['client_uuid'],
                'invoice_no'     => $this->nextInvoiceNo(),
                'user_id'        => $user->id,
                'customer_id'    => $customer?->id,
                'table_label'    => $data['table_label'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'payment_method' => $data['payment_method'],
                'amount_paid'    => $amountPaid,
                'change_amount'  => $data['payment_method'] === 'cash' ? $amountPaid - $total : 0,
                'status'         => $data['payment_method'] === 'debt' ? 'debt' : 'paid',
                'paid_at'        => $data['payment_method'] === 'debt' ? null : now(),
            ]);

            $transaction->items()->createMany($lines);

            return $transaction->load(['items', 'customer']);
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
