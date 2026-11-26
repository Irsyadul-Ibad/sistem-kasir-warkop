<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with('category')->orderBy('name');

        // POS hanya perlu menu aktif; owner dapat meminta menu nonaktif untuk pengelolaan.
        if ($request->user()->role !== 'owner' || ! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return MenuResource::collection($query->get());
    }

    public function store(MenuRequest $request, MenuService $service)
    {
        $menu = $service->create($request->user(), $request->validated());

        return (new MenuResource($menu))
            ->response()
            ->setStatusCode(201);
    }

    public function update(MenuRequest $request, Menu $menu, MenuService $service)
    {
        return new MenuResource($service->update($request->user(), $menu, $request->validated()));
    }
}
