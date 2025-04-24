<?php
/**
 * Database Setup Script for Bloom Bouquet Admin
 * 
 * This script helps check MySQL connection and create the database if it doesn't exist.
 * To run this script, navigate to your project folder in command line and run:
 * php database_setup.php
 */

// Database configuration
$dbHost = '127.0.0.1';
$dbPort = 3307;      // The port specified in your .env file
$dbUser = 'root';    // Default XAMPP MySQL username 
$dbPass = '';        // Default XAMPP MySQL password (blank)
$dbName = 'admin_bloom_bouqet';

echo "======================================\n";
echo "   Bloom Bouquet Database Setup\n";
echo "======================================\n\n";

// Step 1: Check MySQL connection
echo "Step 1: Checking MySQL connection...\n";

try {
    // Try connecting without database name first to check server connection
    $conn = new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Successfully connected to MySQL server at {$dbHost}:{$dbPort}\n\n";
    
    // Step 2: Check if database exists
    echo "Step 2: Checking if database '{$dbName}' exists...\n";
    
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbName}'");
    
    if ($result->num_rows > 0) {
        echo "✓ Database '{$dbName}' already exists.\n\n";
    } else {
        echo "✗ Database '{$dbName}' does not exist.\n";
        echo "Creating database '{$dbName}'...\n";
        
        if ($conn->query("CREATE DATABASE {$dbName}")) {
            echo "✓ Database '{$dbName}' successfully created!\n\n";
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    }
    
    // Step 3: Check if we can connect to the specific database
    echo "Step 3: Testing connection to '{$dbName}' database...\n";
    
    $dbConn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    
    if ($dbConn->connect_error) {
        throw new Exception("Failed to connect to {$dbName} database: " . $dbConn->connect_error);
    }
    
    echo "✓ Successfully connected to '{$dbName}' database!\n\n";
    
    // Step 4: Check Laravel migrations
    echo "Step 4: Checking if Laravel migrations need to be run...\n";
    
    $result = $dbConn->query("SHOW TABLES LIKE 'migrations'");
    
    if ($result->num_rows > 0) {
        echo "✓ Migrations table exists. You may already have run migrations.\n";
        echo "  If you still have issues, try running: php artisan migrate:fresh\n\n";
    } else {
        echo "✗ No migrations table found.\n";
        echo "  You should run migrations using: php artisan migrate\n\n";
    }
    
    // Close connections
    $conn->close();
    if (isset($dbConn)) {
        $dbConn->close();
    }
    
    echo "======================================\n";
    echo "   Database Setup Complete!\n";
    echo "======================================\n\n";
    echo "If Laravel is still having connection issues:\n";
    echo "1. Make sure your .env file has these settings:\n";
    echo "   DB_CONNECTION=mysql\n";
    echo "   DB_HOST=127.0.0.1\n";
    echo "   DB_PORT=3307\n";
    echo "   DB_DATABASE=admin_bloom_bouqet\n";
    echo "   DB_USERNAME=root\n";
    echo "   DB_PASSWORD=\n\n";
    echo "2. Restart your Laravel development server:\n";
    echo "   php artisan serve\n\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting tips:\n";
    echo "1. Make sure MySQL is running in XAMPP Control Panel\n";
    echo "2. Check if MySQL is running on port {$dbPort}\n";
    echo "3. Verify your username and password are correct\n";
    echo "4. Try restarting XAMPP MySQL service\n\n";
    
    echo "For more help, visit the project documentation or contact support.\n";
} 