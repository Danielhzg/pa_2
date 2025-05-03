# Favorite Products API

This document outlines the API endpoints for managing favorite products in the Bloom Bouquet app.

## Authentication

All favorite product endpoints require authentication. Include the `Authorization` header with a valid Bearer token in all requests.

```
Authorization: Bearer {your_token}
```

## Endpoints

### Get User's Favorite Products

Returns all products that have been marked as favorites by the authenticated user.

**Request:**
```
GET /api/v1/favorites
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product": {
        "id": 5,
        "name": "Rose Bouquet",
        "slug": "rose-bouquet",
        "description": "Beautiful red roses",
        "price": 150000,
        "stock": 10,
        "category_id": 1,
        "image": "products/roses.jpg",
        "is_active": true,
        "imageUrl": "https://example.com/storage/products/roses.jpg",
        "category": {
          "id": 1,
          "name": "Bouquets",
          "slug": "bouquets"
        }
      }
    }
  ],
  "message": "Favorite products retrieved successfully"
}
```

### Toggle Favorite Status

Adds or removes a product from the user's favorites.

**Request:**
```
POST /api/v1/favorites/toggle
```

**Body:**
```json
{
  "product_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "is_favorited": true, // or false if removed
  "message": "Product added to favorites" // or "Product removed from favorites"
}
```

### Check Favorite Status

Checks if a specific product is in the user's favorites.

**Request:**
```
GET /api/v1/favorites/check/{productId}
```

**Response:**
```json
{
  "success": true,
  "is_favorited": true, // or false
  "message": "Favorite status checked successfully"
}
```

## Product Responses with Favorite Status

When a user is authenticated, all product responses will include an `is_favorited` field indicating whether the product is in the user's favorites.

Example product response:

```json
{
  "id": 5,
  "name": "Rose Bouquet",
  "slug": "rose-bouquet",
  "description": "Beautiful red roses",
  "price": 150000,
  "stock": 10,
  "category_id": 1,
  "image": "products/roses.jpg",
  "is_active": true,
  "imageUrl": "https://example.com/storage/products/roses.jpg",
  "is_favorited": true, // Will be present for authenticated users
  "category": {
    "id": 1,
    "name": "Bouquets",
    "slug": "bouquets"
  }
}
```

## Notes for Implementation

1. The `is_favorited` field will only be included for authenticated users
2. The toggle endpoint provides an easy way to add/remove favorites without needing separate endpoints
3. Use the check endpoint when you only need to know the favorite status without retrieving the entire product 