<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CarouselController extends Controller
{
    /**
     * Fetch all active carousels.
     */
    public function index(): JsonResponse
    {
        $carousels = Carousel::where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'title', 'description', 'image_url', 'order']);
        return response()->json($carousels);
    }
    
    /**
     * Display a listing of carousels for admin.
     */
    public function adminIndex()
    {
        $carousels = Carousel::orderBy('order')->get();
        return view('admin.carousels.index', compact('carousels'));
    }
    
    /**
     * Show the form for creating a new carousel.
     */
    public function create()
    {
        return view('admin.carousels.create');
    }
    
    /**
     * Store a newly created carousel in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
        ]);
        
        // Handle image uploads
        if ($request->hasFile('image_url')) {
            $image = $request->file('image_url');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/carousels', $imageName);
            $validated['image_url'] = 'carousels/' . $imageName;
        }
        
        // Set admin_id
        $validated['admin_id'] = Auth::guard('admin')->id();
        
        Carousel::create($validated);
        
        return redirect()->route('admin.carousels.index')
            ->with('success', 'Carousel created successfully.');
    }
    
    /**
     * Show the form for editing the specified carousel.
     */
    public function edit(Carousel $carousel)
    {
        return view('admin.carousels.edit', compact('carousel'));
    }
    
    /**
     * Update the specified carousel in storage.
     */
    public function update(Request $request, Carousel $carousel)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
        ]);
        
        // Handle image uploads
        if ($request->hasFile('image_url')) {
            // Delete old image
            if ($carousel->image_url) {
                Storage::delete('public/' . $carousel->image_url);
            }
            
            $image = $request->file('image_url');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/carousels', $imageName);
            $validated['image_url'] = 'carousels/' . $imageName;
        }
        
        // Always set admin_id
            $validated['admin_id'] = Auth::guard('admin')->id();
        
        $carousel->update($validated);
        
        return redirect()->route('admin.carousels.index')
            ->with('success', 'Carousel updated successfully.');
    }
    
    /**
     * Remove the specified carousel from storage.
     */
    public function destroy(Carousel $carousel)
    {
        // Delete images
        if ($carousel->image_url) {
            Storage::delete('public/' . $carousel->image_url);
        }
        
        $carousel->delete();
        
        return redirect()->route('admin.carousels.index')
            ->with('success', 'Carousel deleted successfully.');
    }
}
