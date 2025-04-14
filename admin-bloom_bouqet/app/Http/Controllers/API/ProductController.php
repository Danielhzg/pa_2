<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Return a list of products in JSON format.
     */
    public function index()
    {
        $products = Product::with('category')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'images'])->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        // Transform the product to ensure image URLs are complete
        if ($product->images && $product->images->count() > 0) {
            $product->images->transform(function ($image) {
                $image->url = url('storage/' . $image->path);
                return $image;
            });
            // Add a main_image field for convenience
            $product->main_image = $product->images->first()->url ?? null;
        }
        
        // Set the imageUrl property for client compatibility
        if ($product->main_image) {
            $product->imageUrl = $product->main_image;
        } elseif ($product->image) {
            // If product has an image field but no images relationship
            $product->imageUrl = url('storage/' . $product->image);
        } else {
            // Fallback to a default image
            $product->imageUrl = url('storage/products/default-product.png');
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

    /**
     * Get products filtered by category ID.
     *
     * @param int $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($category)
    {
        try {
            // Load products with category relationship only (removing images relationship for now)
            $products = Product::with('category')
                ->where('category_id', $category)
                ->get();
            
            // Transform the products to ensure image URLs are complete
            $products = $products->map(function ($product) {
                // If the product has an image field directly, use it
                if ($product->image) {
                    $product->imageUrl = url('storage/' . $product->image);
                } else {
                    // Fallback to a default image
                    $product->imageUrl = url('storage/products/default-product.png');
                }
                return $product;
            });
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products: ' . $e->getMessage()
            ], 500);
        }
    }
}
