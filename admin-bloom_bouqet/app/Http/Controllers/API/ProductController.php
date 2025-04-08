<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ]);
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Product details',
            'data' => $product
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        $products = Product::with('category')
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Search results',
            'data' => $products
        ]);
    }
}
