package main

import (
	"fmt"
	"log"
	"os"

	"pa_2/backend/handlers"
	"pa_2/backend/middleware"
	"pa_2/backend/models"

	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/driver/postgres"
	"gorm.io/gorm"
)

var db *gorm.DB

func main() {
    // Load .env file
    err := godotenv.Load()
    if err != nil {
        log.Fatal("Error loading .env file")
    }

    // Connect to database
    dsn := fmt.Sprintf("host=%s user=%s password=%s dbname=%s port=%s sslmode=%s TimeZone=Asia/Jakarta",
        os.Getenv("DB_HOST"),
        os.Getenv("DB_USER"),
        os.Getenv("DB_PASSWORD"),
        os.Getenv("DB_NAME"),
        os.Getenv("DB_PORT"),
        os.Getenv("DB_SSLMODE"),
    )
    db, err = gorm.Open(postgres.Open(dsn), &gorm.Config{})
    if err != nil {
        log.Fatal("Failed to connect to database: ", err)
    }

    // Force drop all tables (only in development)
    if os.Getenv("APP_ENV") != "production" {
        db.Exec("DROP TABLE IF EXISTS users CASCADE")
        db.Exec("DROP TABLE IF EXISTS products CASCADE")
        db.Exec("DROP TABLE IF EXISTS cart_items CASCADE")
        db.Exec("DROP TABLE IF EXISTS orders CASCADE")
        db.Exec("DROP TABLE IF EXISTS order_items CASCADE")
    }

    // Auto migrate all models
    if err := db.AutoMigrate(&models.User{}); err != nil {
        log.Fatal("Failed to migrate database: ", err)
    }

    // Create a test user
    testUser := models.User{
        Username:     "daniel",
        Email:        "daniel@example.com",
        NomorTelepon: "1234567890",
    }
    hashedPassword, _ := bcrypt.GenerateFromPassword([]byte("password123"), bcrypt.DefaultCost)
    testUser.Password = string(hashedPassword)

    // Create user if not exists
    result := db.Where("username = ?", testUser.Username).FirstOrCreate(&testUser)
    if result.Error != nil {
        log.Printf("Error creating test user: %v", result.Error)
    }

    // Setup router
    r := gin.Default()

    // CORS middleware
    r.Use(func(c *gin.Context) {
        c.Writer.Header().Set("Access-Control-Allow-Origin", "*")
        c.Writer.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
        c.Writer.Header().Set("Access-Control-Allow-Methods", "POST, GET, OPTIONS, PUT, DELETE")
        if c.Request.Method == "OPTIONS" {
            c.AbortWithStatus(204)
            return
        }
        c.Next()
    })

    // Initialize handlers
    authHandler := handlers.NewAuthHandler(db)

    // Public routes
    auth := r.Group("/api/auth")
    {
        auth.POST("/register", authHandler.Register)
        auth.POST("/login", authHandler.Login)
    }

    // Protected routes
    api := r.Group("/api")
    api.Use(middleware.AuthMiddleware())
    {
         // User routes
        api.GET("/user/profile", getUserProfile)
        
        // Products
        api.GET("/products", getProducts)
        api.GET("/products/featured", getFeaturedProducts)
        api.GET("/products/search", searchProducts)
        api.GET("/products/:id", getProduct)
    }

    r.Run(":5000")
}
