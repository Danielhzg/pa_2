<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'discount' => 'nullable|integer|min:0|max:100',
        ]);

        // Generate a slug from the name
        $slug = Str::slug($request->name);
        
        // Make sure the slug is unique
        $count = 1;
        $originalSlug = $slug;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        // Handle primary image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        // Create the product
        Product::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'image' => $imagePath,
            'images' => [$imagePath], // Store single image in images array for model compatibility
            'is_active' => $request->has('is_active'),
            'is_on_sale' => $request->has('is_on_sale'),
            'discount' => $request->discount ?? 0,
            'admin_id' => auth()->check() ? auth()->id() : null,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk "'.$request->name.'" berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'discount' => 'nullable|integer|min:0|max:100',
        ]);

        $oldName = $product->name;

        // Update slug if name changed
        if ($request->name !== $product->name) {
            $slug = Str::slug($request->name);
            
            // Make sure the slug is unique
            $count = 1;
            $originalSlug = $slug;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $product->slug = $slug;
        }
        
        // Handle image
        $imagePath = $product->image;
        
        // Handle primary image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            
            $imagePath = $request->file('image')->store('products', 'public');
        }

        // Update the product
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'image' => $imagePath,
            'images' => [$imagePath], // Update images array with single image
            'is_active' => $request->has('is_active'),
            'is_on_sale' => $request->has('is_on_sale'),
            'discount' => $request->discount ?? 0,
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk "'.$oldName.'" berhasil diperbarui menjadi "'.$request->name.'"');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        
        $productName = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk "'.$productName.'" berhasil dihapus');
    }
}
