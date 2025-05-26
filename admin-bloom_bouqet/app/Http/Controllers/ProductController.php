<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category; // Import the Category model
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::with('category')->get();
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        }
        
        return view('admin.products.index', compact('products'));
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all(); // Fetch all categories
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'main_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle primary image
        if ($request->hasFile('main_image')) {
            $image = $request->file('main_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/products', $imageName);
            $validated['main_image'] = 'products/' . $imageName;
        }

        // Handle additional images
        $galleryImages = [];
        if ($request->hasFile('additional_images')) {
            foreach ($request->file('additional_images') as $image) {
                $imageName = time() . '_' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/products', $imageName);
                $galleryImages[] = 'products/' . $imageName;
            }
            $validated['gallery_images'] = $galleryImages;
        }

        // Set admin_id to the current authenticated admin
        $validated['admin_id'] = auth()->guard('admin')->id();

        $product = Product::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        if ($request->wantsJson()) {
            // Add a formatted version of all images for the API
            $product->all_images = $product->getAllImages();
            
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        }

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::all(); // Fetch all categories
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'additional_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remove_images' => 'nullable|array',
        ]);

        // Handle primary image upload
        if ($request->hasFile('main_image')) {
            if ($product->main_image) {
                Storage::delete('public/' . $product->main_image);
            }

            $image = $request->file('main_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/products', $imageName);
            $validated['main_image'] = 'products/' . $imageName;
        }

        // Handle removing images from gallery_images
        if ($request->has('remove_images') && is_array($request->remove_images)) {
            $currentGalleryImages = $product->gallery_images ?? [];
            $newGalleryImages = [];
            
            foreach ($currentGalleryImages as $imagePath) {
                if (!in_array($imagePath, $request->remove_images)) {
                    $newGalleryImages[] = $imagePath;
                } else {
                    // Delete the removed image file
                    Storage::delete('public/' . $imagePath);
                }
            }
            
            $validated['gallery_images'] = $newGalleryImages;
        }

        // Handle additional images upload
        if ($request->hasFile('additional_images')) {
            $currentGalleryImages = $validated['gallery_images'] ?? $product->gallery_images ?? [];
            
            foreach ($request->file('additional_images') as $image) {
                $imageName = time() . '_' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/products', $imageName);
                $currentGalleryImages[] = 'products/' . $imageName;
            }
            
            $validated['gallery_images'] = $currentGalleryImages;
        }

        // Set admin_id if it's not already set
        if (!$product->admin_id) {
            $validated['admin_id'] = auth()->guard('admin')->id();
        }

        $product->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        
        // Delete primary image if exists
        if ($product->main_image) {
            Storage::delete('public/' . $product->main_image);
        }
        
        // Delete gallery images if they exist
        if (!empty($product->gallery_images) && is_array($product->gallery_images)) {
            foreach ($product->gallery_images as $imagePath) {
                Storage::delete('public/' . $imagePath);
            }
        }
        
        $product->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product deleted successfully');
    }
}
