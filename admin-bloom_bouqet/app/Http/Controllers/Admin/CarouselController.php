<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    public function index()
    {
        $carousels = Carousel::orderBy('order')->get();
        return view('admin.carousels.index', compact('carousels'));
    }

    public function create()
    {
        return view('admin.carousels.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image',
            'order' => 'nullable|integer',
        ]);

        $imagePath = $request->file('image')->store('carousels', 'public');

        Carousel::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'order' => $request->order ?? 0,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('admin.carousels.index')->with('success', 'Carousel created successfully.');
    }

    public function edit(Carousel $carousel)
    {
        return view('admin.carousels.edit', compact('carousel'));
    }

    public function update(Request $request, Carousel $carousel)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('carousels', 'public');
            $carousel->image = $imagePath;
        }

        $carousel->update([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order ?? 0,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('admin.carousels.index')->with('success', 'Carousel updated successfully.');
    }

    public function destroy(Carousel $carousel)
    {
        $carousel->delete();
        return redirect()->route('admin.carousels.index')->with('success', 'Carousel deleted successfully.');
    }
}
