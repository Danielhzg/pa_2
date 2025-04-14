<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use Illuminate\Http\JsonResponse;

class CarouselController extends Controller
{
    /**
     * Fetch all active carousels.
     */
    public function index(): JsonResponse
    {
        $carousels = Carousel::where('active', true)
            ->orderBy('order')
            ->get(['id', 'title', 'description', 'image', 'order']);
        return response()->json($carousels);
    }
}
