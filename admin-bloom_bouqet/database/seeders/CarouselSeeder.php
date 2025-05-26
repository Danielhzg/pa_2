<?php

namespace Database\Seeders;

use App\Models\Carousel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CarouselSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing carousels
        Carousel::truncate();
        
        // Create directory if it doesn't exist
        $storagePath = storage_path('app/public/carousels');
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }
        
        // Sample carousel data
        $carousels = [
            [
                'title' => 'Welcome to Bloom Bouquet',
                'description' => 'Discover our beautiful flower arrangements',
                'image_url' => 'carousels/welcome_banner.jpg',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'title' => 'Special Promotion',
                'description' => 'Get 15% off on all bouquets this week',
                'image_url' => 'carousels/promo_banner.jpg',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'title' => 'Wedding Collection',
                'description' => 'Perfect flowers for your special day',
                'image_url' => 'carousels/wedding_banner.jpg',
                'is_active' => true,
                'order' => 3,
            ],
        ];
        
        // Create sample images
        $this->createSampleImage($storagePath . '/welcome_banner.jpg', 'Welcome Banner', [255, 200, 200]);
        $this->createSampleImage($storagePath . '/promo_banner.jpg', 'Promo Banner', [200, 255, 200]);
        $this->createSampleImage($storagePath . '/wedding_banner.jpg', 'Wedding Banner', [200, 200, 255]);
        
        // Create carousel records
        foreach ($carousels as $carousel) {
            Carousel::create($carousel);
        }
        
        $this->command->info('Created ' . count($carousels) . ' carousel items with sample images');
    }
    
    /**
     * Create a sample image with text
     */
    private function createSampleImage($path, $text, $bgColor = [255, 255, 255])
    {
        // Create image
        $width = 1200;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color
        $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        imagefill($image, 0, 0, $bg);
        
        // Add text
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $font = 5; // Built-in font
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save image
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }
} 