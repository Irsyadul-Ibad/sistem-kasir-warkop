<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\Request;

class OwnerMenuController extends Controller
{
    public function index()
    {
        return view('owner.menus.index', [
            'menus' => Menu::with('category')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('owner.menus.form', [
            'menu' => new Menu(['is_active' => true]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request, MenuService $service)
    {
        $data = $this->validated($request);
        $service->create($request->user(), $data);

        return redirect()->route('owner.menus.index')->with('status', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        return view('owner.menus.form', [
            'menu' => $menu,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Menu $menu, MenuService $service)
    {
        $service->update($request->user(), $menu, $this->validated($request));

        return redirect()->route('owner.menus.index')->with('status', 'Perubahan menu berhasil disimpan.');
    }

    private function categories()
    {
        return Category::query()
            ->where('type', 'menu')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
