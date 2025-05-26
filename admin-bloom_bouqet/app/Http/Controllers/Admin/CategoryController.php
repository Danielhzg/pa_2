<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        // Generate a slug from the name
        $slug = Str::slug($request->name);
        
        // Make sure the slug is unique
        $count = 1;
        $originalSlug = $slug;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category = new Category();
        $category->name = $request->name;
        $category->slug = $slug;
        $category->admin_id = Auth::guard('admin')->id();
        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori "'.$request->name.'" berhasil ditambahkan');
    }

    /**
     * Display the specified category.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $oldName = $category->name;

        // Generate a new slug if the name has changed
        if ($request->name !== $category->name) {
            $slug = Str::slug($request->name);
            
            // Make sure the slug is unique
            $count = 1;
            $originalSlug = $slug;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $category->slug = $slug;
        }

        $category->name = $request->name;
        
        // If admin_id is null, set it to current admin
        if (!$category->admin_id) {
            $category->admin_id = Auth::guard('admin')->id();
        }
        
        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori "'.$oldName.'" berhasil diperbarui menjadi "'.$request->name.'"');
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        // Check if the category has associated products
        $productCount = $category->products()->count();
        
        if ($productCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('warning', 'Kategori "'.$category->name.'" tidak dapat dihapus karena masih memiliki '.$productCount.' produk terkait. Silakan pindahkan produk ke kategori lain terlebih dahulu.');
        }
        
        try {
            DB::beginTransaction();
            
            $categoryName = $category->name;
            $category->delete();
            
            DB::commit();
            
            return redirect()->route('admin.categories.index')
                ->with('success', 'Kategori "'.$categoryName.'" berhasil dihapus');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.categories.index')
                ->with('error', 'Terjadi kesalahan saat menghapus kategori. Silakan coba lagi.');
        }
    }
    
    /**
     * Move products to another category and delete the original category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function deleteWithProducts(Request $request, Category $category)
    {
        $request->validate([
            'target_category_id' => 'required|exists:categories,id|different:id',
        ]);
        
        $productCount = $category->products()->count();
        $targetCategory = Category::findOrFail($request->target_category_id);
        
        try {
            DB::beginTransaction();
            
            // Move all products to the target category
            Product::where('category_id', $category->id)
                ->update(['category_id' => $targetCategory->id]);
            
            // Delete the category
            $categoryName = $category->name;
        $category->delete();
            
            DB::commit();
            
            return redirect()->route('admin.categories.index')
                ->with('success', 'Kategori "'.$categoryName.'" berhasil dihapus dan '.$productCount.' produk telah dipindahkan ke kategori "'.$targetCategory->name.'".');
                
        } catch (\Exception $e) {
            DB::rollBack();
        return redirect()->route('admin.categories.index')
                ->with('error', 'Terjadi kesalahan saat menghapus kategori dan memindahkan produk. Silakan coba lagi.');
        }
    }
}
