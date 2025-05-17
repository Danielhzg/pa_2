<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        try {
            // Get counts for dashboard stats
            $totalOrders = Order::count();
            $totalProducts = Product::count();
            $totalCustomers = User::count();
            $totalRevenue = Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total_amount');
            
            // Get recent orders with unread status
            $recentOrders = Order::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            // Get popular products
            $popularProducts = Product::withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->limit(5)
                ->get();
                
            // Get recent customers
            $recentCustomers = User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            return view('admin.dashboard', compact(
                'totalOrders',
                'totalProducts',
                'totalCustomers',
                'totalRevenue',
                'recentOrders',
                'popularProducts',
                'recentCustomers'
            ));
        } catch (\Exception $e) {
            Log::error('Error in AdminController@index: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat dashboard.');
        }
    }

    /**
     * Store a newly created category in storage.
     */
    public function storeCategory(Request $request)
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

        Category::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori "' . $request->name . '" berhasil ditambahkan');
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
            
            $category->update([
                'name' => $request->name,
                'slug' => $slug,
            ]);
        } else {
            $category->update([
                'name' => $request->name,
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Kategori "' . $oldName . '" berhasil diperbarui menjadi "' . $request->name . '"');
    }

    /**
     * Remove the specified category from storage.
     */
    public function deleteCategory(Category $category)
    {
        // Check if the category has associated products
        if ($category->products->count() > 0) {
            return redirect()->route('admin.categories.index')
                ->with('warning', 'Kategori "'.$category->name.'" tidak dapat dihapus karena masih memiliki '.$category->products->count().' produk terkait. Silakan pindahkan produk ke kategori lain terlebih dahulu.');
        }
        
        try {
            // Safely delete the category
            $categoryName = $category->name;
            $category->delete();
            
            return redirect()->route('admin.categories.index')
                ->with('success', 'Kategori "'.$categoryName.'" berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Terjadi kesalahan saat menghapus kategori. Silakan coba lagi.');
        }
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
        $productName = $product->name;
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk "' . $productName . '" berhasil dihapus');
    }

    /**
     * Display the admin's profile.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

    /**
     * Update the admin's profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);
        
        $userData = [
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
        ];
        
        // Update password if provided
        if ($request->filled('current_password') && $request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.'])->withInput();
            }
            
            $userData['password'] = Hash::make($request->new_password);
        }
        
        $user->update($userData);
        
        return redirect()->route('admin.profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
