package main

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

func getProducts(c *gin.Context) {
    var products []Product
    result := db.Find(&products)
    if result.Error != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch products"})
        return
    }
    c.JSON(http.StatusOK, products)
}

func getFeaturedProducts(c *gin.Context) {
    var products []Product
    result := db.Where("featured = ?", true).Find(&products)
    if result.Error != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to fetch featured products"})
        return
    }
    c.JSON(http.StatusOK, products)
}

func searchProducts(c *gin.Context) {
    query := c.Query("query")
    var products []Product
    
    result := db.Where("name ILIKE ? OR description ILIKE ?", 
        "%"+query+"%", "%"+query+"%").Find(&products)
    
    if result.Error != nil {
        c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to search products"})
        return
    }
    c.JSON(http.StatusOK, products)
}

func getProduct(c *gin.Context) {
    id := c.Param("id")
    var product Product
    
    if err := db.First(&product, id).Error; err != nil {
        c.JSON(http.StatusNotFound, gin.H{"error": "Product not found"})
        return
    }
    
    c.JSON(http.StatusOK, product)
}
