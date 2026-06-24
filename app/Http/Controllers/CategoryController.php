<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->with('company')
            ->withCount('products')
            ->orderBy('id')
            ->get();

        return view('categories.index', [
            'categories' => $categories,
        ]);
    }
}
