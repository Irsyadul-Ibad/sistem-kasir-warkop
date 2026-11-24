<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        $category = Category::create(['name' => 'Minuman']);
        $kopi = Menu::create(['category_id' => $category->id, 'name' => 'Kopi Hitam', 'price' => 5000]);
        $mie  = Menu::create(['category_id' => $category->id, 'name' => 'Indomie Telur', 'price' => 12000]);

        return array_merge([
            'client_uuid'    => '7b0c3a52-1d4e-4c2a-9b7f-3e5a8c1d2f00',
            'payment_method' => 'cash',
            'amount_paid'    => 30000,
            'items'          => [
                ['menu_id' => $kopi->id, 'qty' => 2],
                ['menu_id' => $mie->id, 'qty' => 1],
            ],
        ], $override);
    }

    private function cashier(): User
    {
        $user = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_total_dan_kembalian_dihitung_server(): void
    {
        $this->cashier();

        $this->postJson('/api/v1/transactions', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.total', 22000)
            ->assertJsonPath('data.change_amount', 8000);
    }

    public function test_harga_dari_klien_diabaikan(): void
    {
        $this->cashier();
        $payload = $this->payload();
        $payload['items'][0]['price'] = 1;

        $this->postJson('/api/v1/transactions', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 22000);
    }

    public function test_client_uuid_sama_tidak_membuat_duplikat(): void
    {
        $this->cashier();
        $payload = $this->payload();

        $this->postJson('/api/v1/transactions', $payload)->assertCreated();
        $this->postJson('/api/v1/transactions', $payload)->assertOk();

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_uang_kurang_ditolak(): void
    {
        $this->cashier();

        $this->postJson('/api/v1/transactions', $this->payload(['amount_paid' => 10000]))
            ->assertStatus(422);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->postJson('/api/v1/transactions', [])->assertUnauthorized();
    }
}