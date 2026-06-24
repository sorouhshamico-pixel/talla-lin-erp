<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['category', 'variants'])
            ->withCount('variants')
            ->orderBy('id')
            ->get();

        return view('products.index', [
            'products' => $products,
        ]);
    }
}
