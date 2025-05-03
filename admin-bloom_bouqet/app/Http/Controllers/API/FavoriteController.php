<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    /**
     * Get all favorite products for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('Unauthorized access attempt to favorites index');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            Log::info('User ' . $user->id . ' retrieving favorites');
            
            $favorites = FavoriteProduct::where('user_id', $user->id)
                ->with(['product', 'product.category'])
                ->get();
            
            // Transform the data for easier parsing in Flutter
            $formattedFavorites = $favorites->map(function($favorite) {
                // Ensure product exists
                if (!$favorite->product) {
                    return [
                        'id' => $favorite->id,
                        'user_id' => $favorite->user_id,
                        'product_id' => $favorite->product_id,
                        'created_at' => $favorite->created_at,
                        'updated_at' => $favorite->updated_at,
                        'product' => null
                    ];
                }
                
                // Format product data
                $product = $favorite->product;
                
                // Make sure image URL is set
                $imageUrl = null;
                if (isset($product->image)) {
                    $imageUrl = url('storage/' . $product->image);
                }
                
                return [
                    'id' => $favorite->id,
                    'user_id' => $favorite->user_id,
                    'product_id' => $favorite->product_id,
                    'created_at' => $favorite->created_at,
                    'updated_at' => $favorite->updated_at,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'image' => $product->image,
                        'image_url' => $imageUrl,
                        'stock' => $product->stock ?? 0,
                        'category_id' => $product->category_id,
                        'category_name' => $product->category ? $product->category->name : 'Uncategorized',
                        'is_on_sale' => (bool)($product->is_on_sale ?? false),
                        'discount' => $product->discount ?? 0,
                    ]
                ];
            });
                
            Log::info('Retrieved ' . $favorites->count() . ' favorites for user ' . $user->id);
                
            return response()->json([
                'success' => true,
                'data' => $formattedFavorites
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving favorites: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving favorites: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle favorite status for a product.
     */
    public function toggle(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('Unauthorized access attempt to favorites toggle');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            Log::info('User ' . $user->id . ' toggling favorite');
            
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);
            
            $productId = $request->product_id;
            Log::info('Toggling favorite for product ' . $productId . ' by user ' . $user->id);
            
            // Check if already favorited
            $existing = FavoriteProduct::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();
                
            if ($existing) {
                // Remove from favorites
                Log::info('Removing product ' . $productId . ' from favorites for user ' . $user->id);
                $existing->delete();
                
                return response()->json([
                    'success' => true,
                    'is_favorited' => false,
                    'message' => 'Product removed from favorites'
                ]);
            } else {
                // Add to favorites
                Log::info('Adding product ' . $productId . ' to favorites for user ' . $user->id);
                FavoriteProduct::create([
                    'user_id' => $user->id,
                    'product_id' => $productId
                ]);
                
                return response()->json([
                    'success' => true,
                    'is_favorited' => true,
                    'message' => 'Product added to favorites'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error toggling favorite: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error toggling favorite status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if a product is favorited by the authenticated user.
     */
    public function check($productId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('Unauthorized access attempt to favorites check');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            Log::info('User ' . $user->id . ' checking favorite status for product ' . $productId);
            
            $exists = FavoriteProduct::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();
                
            return response()->json([
                'success' => true,
                'is_favorited' => $exists
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking favorite status: ' . $e->getMessage() . ' / ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error checking favorite status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get the primary image URL for a product
     * 
     * @param Product $product
     * @return string
     */
    private function getProductImageUrl($product)
    {
        $primaryImage = $product->getPrimaryImage();
        
        if ($primaryImage) {
            return url('storage/' . $primaryImage);
        }
        
        // Fallback to a default image
        return url('storage/products/default-product.png');
    }
} 