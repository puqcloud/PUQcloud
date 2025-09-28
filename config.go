package main

import (
	"flag"
	"fmt"
	"os"
)

type Config struct {
	PuqcloudIP string
	ApiKey     string
	Port       int
	Debug      bool
}

// ParseFlags parses CLI flags and returns a Config struct
func ParseFlags() *Config {
	cfg := &Config{}

	// Flags
	puqcloudIP := flag.String("puqcloud_ip", "", "IP address of PUQcloud (required)")
	apiKey := flag.String("api_key", "", "API key for authentication (required)")
	port := flag.Int("port", 8080, "Port for the proxy (optional, default: 8080)")
	debug := flag.Bool("debug", false, "Enable debug mode (optional)")
	showVersion := flag.Bool("v", false, "Show version and exit")

	// Custom usage message
	flag.Usage = func() {
		fmt.Fprintf(os.Stderr, "Usage: vncwebproxy [options]\n\n")
		flag.PrintDefaults()
		fmt.Fprintf(os.Stderr, "\nExample:\n")
		fmt.Fprintf(os.Stderr, "  vncwebproxy -puqcloud_ip=192.168.0.10 -api_key=12345 -port=9090 -debug\n")
	}

	// Parse flags
	flag.Parse()

	// Version check
	if *showVersion {
		fmt.Println("Proxy version:", Version)
		os.Exit(0)
	}

	// Required flags validation
	if *puqcloudIP == "" || *apiKey == "" {
		fmt.Println("Error: -puqcloud_ip and -api_key are required")
		fmt.Println()
		flag.Usage()
		os.Exit(1)
	}

	// Fill config struct
	cfg.PuqcloudIP = *puqcloudIP
	cfg.ApiKey = *apiKey
	cfg.Port = *port
	cfg.Debug = *debug

	return cfg
}
