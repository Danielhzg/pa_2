<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    /**
     * Get active carousels for the Flutter app
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $carousels = Carousel::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $carousels,
            'message' => 'Carousels retrieved successfully'
        ]);
    }
    
    /**
     * Get a specific carousel
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $carousel = Carousel::active()->find($id);
        
        if (!$carousel) {
            return response()->json([
                'success' => false,
                'message' => 'Carousel not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $carousel,
            'message' => 'Carousel retrieved successfully'
        ]);
    }
} 