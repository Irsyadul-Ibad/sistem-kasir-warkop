<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->validate(['type' => ['nullable', 'in:menu,expense']])['type'] ?? 'menu';

        return response()->json([
            'data' => Category::query()
                ->where('type', $type)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(ExpenseCategoryRequest $request)
    {
        $category = Category::firstOrCreate(
            ['name' => trim($request->validated('name')), 'type' => 'expense'],
            ['sort_order' => $request->integer('sort_order')],
        );

        return response()->json(['data' => $category->only(['id', 'name'])], $category->wasRecentlyCreated ? 201 : 200);
    }
}
