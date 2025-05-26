<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Return a list of products in JSON format.
     */
    public function index(Request $request)
    {
        $products = Product::with('category')->get();
        $user = $request->user();
        
        // Process each product to add image URLs
        $products->transform(function ($product) use ($user) {
            // Add imageUrl for all products
            $product->imageUrl = $this->getProductImageUrl($product);
            
            // Add favorite status if user is authenticated
            if ($user) {
                $product->is_favorited = $user->hasFavorited($product->id);
            }
            
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show(Request $request, $id)
    {
        $product = Product::with('category')->find($id);
        $user = $request->user();
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        // Process image data for the product
        $product = $this->processProductImages($product);
        
        // Add favorite status if user is authenticated
        if ($user) {
            $product->is_favorited = $user->hasFavorited($product->id);
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
        $user = $request->user();
        $products = Product::with('category')
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->latest()
            ->get();
            
        // Process each product to add image URLs
        $products->transform(function ($product) use ($user) {
            // Add imageUrl for all products
            $product->imageUrl = $this->getProductImageUrl($product);
            
            // Add favorite status if user is authenticated
            if ($user) {
                $product->is_favorited = $user->hasFavorited($product->id);
            }
            
            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Search results',
            'data' => $products
        ]);
    }

    /**
     * Get products filtered by category ID.
     *
     * @param Request $request
     * @param int $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory(Request $request, $category)
    {
        try {
            $products = Product::with('category')
                ->where('category_id', $category)
                ->get();
            
            $user = $request->user();
            
            // Process each product to add image URLs
            $products->transform(function ($product) use ($user) {
                // Add imageUrl for all products
                $product->imageUrl = $this->getProductImageUrl($product);
                
                // Add favorite status if user is authenticated
                if ($user) {
                    $product->is_favorited = $user->hasFavorited($product->id);
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
    
    /**
     * Process product images to generate URLs for front-end consumption
     * 
     * @param Product $product
     * @return Product
     */
    private function processProductImages(Product $product)
    {
        // Get all images as an array
        $allImages = $product->getAllImages();
        
        // Create array of image URLs for the front-end
        $imageUrls = [];
        foreach ($allImages as $image) {
            $imageUrls[] = url('storage/' . $image);
        }
        
        // Add image URLs to the product
        $product->image_urls = $imageUrls;
        
        // Set the primary image URL
        $product->imageUrl = $this->getProductImageUrl($product);
        
        return $product;
    }
    
    /**
     * Get the primary image URL for a product
     * 
     * @param Product $product
     * @return string
     */
    private function getProductImageUrl(Product $product)
    {
        $primaryImage = $product->getPrimaryImage();
        
        if ($primaryImage) {
            // Ensure we return a full URL with domain
            return url('storage/' . $primaryImage);
        }
        
        // Fallback to a default image
        return url('storage/products/default-product.png');
    }

    /**
     * Check stock availability for multiple products
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStock(Request $request)
    {
        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $request->input('items');
        $insufficientItems = [];

        // Check stock for each product
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            
            if ($product && $product->stock < $item['quantity']) {
                // Product has insufficient stock
                $insufficientItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $item['quantity'],
                    'available' => $product->stock,
                ];
            }
        }

        if (count($insufficientItems) > 0) {
            // Some products have insufficient stock
            return response()->json([
                'success' => false,
                'message' => 'Some products have insufficient stock',
                'insufficient_items' => $insufficientItems,
            ]);
        }

        // All products have sufficient stock
        return response()->json([
            'success' => true,
            'message' => 'All products have sufficient stock',
        ]);
    }
}
