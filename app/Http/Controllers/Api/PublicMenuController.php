<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Models\Menu;

class PublicMenuController extends Controller
{
    public function index()
    {
        return MenuResource::collection(
            Menu::with('category')->where('is_active', true)->orderBy('name')->get()
        );
    }
}
