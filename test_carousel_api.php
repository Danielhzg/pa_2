<?php
// Simple script to test the carousel API endpoint

$api_url = 'http://localhost:8000/api/v1/carousels';
echo "Testing API endpoint: $api_url\n";

// Initialize cURL session
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL session and get the response
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit(1);
}

// Get HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: $http_code\n";

// Close cURL session
curl_close($ch);

// Process and display the response
echo "API Response:\n";
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error decoding JSON: " . json_last_error_msg() . "\n";
    echo "Raw response: " . substr($response, 0, 1000) . "\n";
    exit(1);
}

// Pretty print the response data
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

// Check if carousel data exists and has images
if (isset($data['data']) && is_array($data['data'])) {
    echo "\nFound " . count($data['data']) . " carousels\n";
    
    foreach ($data['data'] as $index => $carousel) {
        echo "\nCarousel #" . ($index + 1) . ":\n";
        echo "  ID: " . ($carousel['id'] ?? 'N/A') . "\n";
        echo "  Title: " . ($carousel['title'] ?? 'N/A') . "\n";
        echo "  Image URL: " . ($carousel['image_url'] ?? 'N/A') . "\n";
        echo "  Full Image URL: " . ($carousel['full_image_url'] ?? 'N/A') . "\n";
        echo "  Active: " . (isset($carousel['is_active']) && $carousel['is_active'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "\nNo carousel data found or invalid response structure\n";
} 