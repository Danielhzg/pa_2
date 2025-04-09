<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $totalProducts = Product::count(); // Get the total number of products
        $totalCategories = Category::count(); // Get the total number of categories
        $totalOrders = \App\Models\Order::count(); // Get the total number of orders
        $totalCustomers = \App\Models\User::where('role', 'customer')->count(); // Get the total number of customers

        return view('admin.dashboard', compact('totalProducts', 'totalCategories', 'totalOrders', 'totalCustomers'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category added successfully.');
    }

    /**
     * Display a listing of categories.
     */
    public function listCategories()
    {
        $categories = Category::all();
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function createCategory()
    {
        return view('admin.categories.create');
    }

    /**
     * Show the form for editing a category.
     */
    public function editCategory(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function deleteCategory(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    /**
     * Display a listing of products.
     */
    public function listProducts()
    {
        $products = Product::with('category')->get(); // Eager-load category relationship
        $categories = Category::all(); // Fetch all categories
        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Remove the specified product from storage.
     */
    public function deleteProduct(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
