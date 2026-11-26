<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MenuService
{
    public function create(User $user, array $data): Menu
    {
        return DB::transaction(function () use ($user, $data) {
            $menu = Menu::create($data);
            $this->audit($user, 'menu.created', $menu, null);

            return $menu->load('category');
        });
    }

    public function update(User $user, Menu $menu, array $data): Menu
    {
        return DB::transaction(function () use ($user, $menu, $data) {
            $before = $menu->only(['name', 'category_id', 'price', 'is_active']);
            $menu->update($data);
            $this->audit($user, 'menu.updated', $menu, $before);

            return $menu->load('category');
        });
    }

    private function audit(User $user, string $action, Menu $menu, ?array $before): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => 'menu',
            'entity_id' => $menu->id,
            'before_data' => $before,
            'after_data' => $menu->only(['name', 'category_id', 'price', 'is_active']),
        ]);
    }
}
