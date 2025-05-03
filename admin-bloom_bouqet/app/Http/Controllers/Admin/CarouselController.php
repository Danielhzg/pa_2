<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarouselController extends Controller
{
    public function index()
    {
        $carousels = Carousel::all();
        return view('admin.carousels.index', compact('carousels'));
    }

    public function create()
    {
        return view('admin.carousels.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image',
        ]);

        $imagePath = $request->file('image')->store('carousels', 'public');

        Carousel::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.carousels.index')->with('success', 'Carousel "'.$request->title.'" berhasil ditambahkan');
    }

    public function edit(Carousel $carousel)
    {
        return view('admin.carousels.edit', compact('carousel'));
    }

    public function update(Request $request, Carousel $carousel)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
        ]);

        $oldTitle = $carousel->title;
        
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($carousel->image && Storage::disk('public')->exists($carousel->image)) {
                Storage::disk('public')->delete($carousel->image);
            }
            
            $imagePath = $request->file('image')->store('carousels', 'public');
            $data['image'] = $imagePath;
        }

        $carousel->update($data);

        return redirect()->route('admin.carousels.index')->with('success', 'Carousel "'.$oldTitle.'" berhasil diperbarui menjadi "'.$request->title.'"');
    }

    public function destroy(Carousel $carousel)
    {
        // Delete image if exists
        if ($carousel->image && Storage::disk('public')->exists($carousel->image)) {
            Storage::disk('public')->delete($carousel->image);
        }
        
        $carouselTitle = $carousel->title;
        $carousel->delete();
        return redirect()->route('admin.carousels.index')->with('success', 'Carousel "'.$carouselTitle.'" berhasil dihapus');
    }
    
    public function toggleActive(Carousel $carousel)
    {
        $status = !$carousel->is_active;
        $carousel->update([
            'is_active' => $status
        ]);
        
        return redirect()->route('admin.carousels.index')
            ->with('success', 'Status carousel "' . $carousel->title . '" berhasil diubah menjadi ' . ($status ? 'aktif' : 'nonaktif'));
    }
}
