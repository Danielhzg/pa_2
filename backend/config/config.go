package config

import (
	"fmt"
	"os"
)

func ValidateEnv() error {
	required := []string{
		"DB_HOST",
		"DB_USER",
		"DB_PASSWORD",
		"DB_NAME",
		"DB_PORT",
		"JWT_SECRET",
	}

	for _, v := range required {
		if os.Getenv(v) == "" {
			return fmt.Errorf("environment variable %s is required", v)
		}
	}

	return nil
}

func GetDatabaseURL() string {
	return fmt.Sprintf(
		"host=%s user=%s password=%s dbname=%s port=%s sslmode=%s",
		os.Getenv("DB_HOST"),
		os.Getenv("DB_USER"),
		os.Getenv("DB_PASSWORD"),
		os.Getenv("DB_NAME"),
		os.Getenv("DB_PORT"),
		os.Getenv("DB_SSLMODE"),
	)
}

func GetJWTSecret() []byte {
	return []byte(os.Getenv("JWT_SECRET"))
}
