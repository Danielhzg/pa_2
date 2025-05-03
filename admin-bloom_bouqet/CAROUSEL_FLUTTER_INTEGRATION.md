# Carousel Flutter Integration Guide

This guide explains how the carousel images created in the Laravel admin panel are displayed in the Flutter application.

## Overview

The carousel integration follows these steps:
1. Admin creates and manages carousels in the Laravel admin panel
2. Laravel provides an API to serve carousel data
3. Flutter app fetches and displays the carousel data

## Backend (Laravel) Components

### 1. Carousel Model

The `Carousel` model has been simplified to include only essential fields:
- `title` - The carousel title
- `description` - The carousel description (optional)
- `image` - The carousel image path
- `is_active` - Flag to show/hide the carousel

The model includes a computed `image_url` property that provides the full URL to the image for the API.

### 2. API Controller

A dedicated API controller (`API/CarouselController.php`) has been created to serve carousel data to the Flutter app:
- `index()` - Returns all active carousels
- `show($id)` - Returns a specific carousel by ID

### 3. API Routes

The API routes are defined in `routes/api.php`:
```php
Route::get('carousels', [CarouselController::class, 'index']);
Route::get('carousels/{id}', [CarouselController::class, 'show']);
```

## Frontend (Flutter) Components

### 1. Carousel Model

A Dart model class (`lib/models/carousel.dart`) that maps the JSON response from the API to a Flutter object.

### 2. Carousel Service

A service class (`lib/services/carousel_service.dart`) that handles API communication:
- `getCarousels()` - Fetches all active carousels
- `getCarouselById(id)` - Fetches a specific carousel

### 3. Carousel Slider Widget

A Flutter widget (`lib/widgets/carousel_slider_widget.dart`) that:
- Loads carousels from the API
- Displays them in an auto-playing slider
- Handles loading states and errors

## How to Use the Carousel in Flutter

1. Import the carousel widget:
```dart
import 'package:your_app/widgets/carousel_slider_widget.dart';
```

2. Add it to your layout:
```dart
@override
Widget build(BuildContext context) {
  return Scaffold(
    appBar: AppBar(title: Text('Home')),
    body: SingleChildScrollView(
      child: Column(
        children: [
          // Carousel at the top
          CarouselSliderWidget(),
          
          // Other content below
          // ...
        ],
      ),
    ),
  );
}
```

## Important Notes

1. Make sure the storage directory is accessible via URL
2. Images uploaded through the admin panel should have appropriate permissions
3. The `image_url` field is crucial for Flutter to display images correctly
4. Only carousels marked as "Active" will be shown in the Flutter app

## Troubleshooting

If carousels are not appearing in the Flutter app:

1. Check that the API endpoints are accessible (use Postman or another API client)
2. Verify that images are correctly stored and have public URLs
3. Make sure the Flutter app has internet permissions
4. Check that the base URL in Flutter's constants file is correct

## API Response Format

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Welcome to Bloom Bouquet",
      "description": "Discover our beautiful flower arrangements",
      "image": "carousels/abc123.jpg",
      "image_url": "https://your-domain.com/storage/carousels/abc123.jpg",
      "is_active": true,
      "created_at": "2023-05-15T08:30:00.000000Z",
      "updated_at": "2023-05-15T08:30:00.000000Z"
    }
  ],
  "message": "Carousels retrieved successfully"
}
``` 