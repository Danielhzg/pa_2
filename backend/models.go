package main

import (
	"time"

	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
)

type User struct {
	gorm.Model
	Username     string     `gorm:"unique;not null;size:50"`
	Email        string     `gorm:"unique;not null;size:100"`
	Password     string     `json:"-" gorm:"not null"`
	NomorTelepon string     `gorm:"size:15"`
	Cart         []CartItem `gorm:"foreignKey:UserID"`
	Orders       []Order    `gorm:"foreignKey:UserID"`
}

type Product struct {
	gorm.Model
	Name        string    `gorm:"not null;size:100"`
	Description string    `gorm:"type:text"`
	Price       float64   `gorm:"not null"`
	ImageURL    string    `gorm:"size:255"`
	Stock       int       `gorm:"not null;default:0"`
	Category    string    `gorm:"size:50"`
	Featured    bool      `gorm:"default:false"`
	CartItems   []CartItem
}

type CartItem struct {
	gorm.Model
	UserID    uint    `gorm:"not null"`
	ProductID uint    `gorm:"not null"`
	Quantity  int     `gorm:"not null;default:1"`
	Product   Product `gorm:"foreignKey:ProductID"`
}

type Order struct {
	gorm.Model
	UserID      uint       `gorm:"not null"`
	Status      string     `gorm:"not null;default:'pending'"`
	TotalAmount float64    `gorm:"not null"`
	OrderDate   time.Time  `gorm:"not null"`
	Items       []OrderItem
}

type OrderItem struct {
	gorm.Model
	OrderID   uint    `gorm:"not null"`
	ProductID uint    `gorm:"not null"`
	Quantity  int     `gorm:"not null"`
	Price     float64 `gorm:"not null"`
	Product   Product `gorm:"foreignKey:ProductID"`
}

func (u *User) HashPassword() error {
	bytes, err := bcrypt.GenerateFromPassword([]byte(u.Password), 14)
	if err != nil {
		return err
	}
	u.Password = string(bytes)
	return nil
}

func (u *User) CheckPassword(password string) error {
	return bcrypt.CompareHashAndPassword([]byte(u.Password), []byte(password))
}
