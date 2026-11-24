<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with('category')->orderBy('name');

        //kasir hanya melihat menu aktif; owner melihat semua
        if ($request->user()->role === 'owner') {
            $query->where('is_active', true);
        }

        return MenuResource::collection($query->get());
    }

    public function store(MenuRequest $request)
    {
        $menu = Menu::create($request->validated());

        return (new MenuResource($menu->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(MenuRequest $request, Menu $menu)
    {
        $menu->update($request->validated());

        return new MenuResource($menu->load('category'));
    }
}
