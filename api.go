package main

import (
	"crypto/tls"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/gorilla/websocket"
)

// Global in-memory store
var proxied = NewProxiedList(time.Minute)

// Struct for POST body
type ProxyRequest struct {
	Hash                string `json:"hash" binding:"required"`
	Token               string `json:"proxmox_token"`
	Cookie              string `json:"cookie"`
	CSRFPreventionToken string `json:"csrfp_revention_token"`
	URL                 string `json:"proxmox_ws_url" binding:"required"`
}

// POST /api/proxy
func proxyHandler(cfg *Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req ProxyRequest
		clientIP := c.ClientIP()

		fmt.Printf("[INFO] Received proxy request from %s\n", clientIP)

		if err := c.ShouldBindJSON(&req); err != nil {
			fmt.Printf("[ERROR] Invalid JSON payload from %s: %v\n", clientIP, err)
			if cfg.Debug {
				fmt.Printf("[DEBUG] JSON binding error details: %v\n", err)
				fmt.Printf("[DEBUG] Request headers: %v\n", c.Request.Header)
			}
			c.JSON(http.StatusBadRequest, gin.H{
				"status": "error",
				"errors": []string{"Invalid JSON or missing fields"},
			})
			return
		}

		fmt.Printf("[INFO] Successfully parsed proxy request with hash: %s\n", req.Hash)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Full proxy request details: hash=%s, token_length=%d, url=%s\n",
				req.Hash, len(req.Token), req.URL)
			fmt.Printf("[DEBUG] Request Content-Type: %s\n", c.GetHeader("Content-Type"))
		}

		// API Key check
		apiKey := c.GetHeader("X-API-Key")
		if apiKey == "" {
			apiKey = c.Query("api_key")
			if cfg.Debug {
				fmt.Printf("[DEBUG] API key found in query parameter\n")
			}
		} else if cfg.Debug {
			fmt.Printf("[DEBUG] API key found in header\n")
		}

		if cfg.Debug {
			if apiKey != "" {
				fmt.Printf("[DEBUG] API key length: %d characters\n", len(apiKey))
			} else {
				fmt.Printf("[DEBUG] No API key provided\n")
			}
		}

		if apiKey != cfg.ApiKey {
			fmt.Printf("[ERROR] Authentication failed for IP %s - invalid API key\n", clientIP)
			if cfg.Debug {
				fmt.Printf("[DEBUG] Expected key length: %d, received key length: %d\n",
					len(cfg.ApiKey), len(apiKey))
			}
			c.JSON(http.StatusUnauthorized, gin.H{
				"status": "error",
				"errors": []string{"Invalid API Key"},
			})
			return
		}

		fmt.Printf("[INFO] API key validation passed for %s\n", clientIP)

		// Client IP check
		if cfg.Debug {
			fmt.Printf("[DEBUG] Checking IP authorization: client=%s, allowed=%s\n",
				clientIP, cfg.PuqcloudIP)
		}

		if clientIP != cfg.PuqcloudIP {
			fmt.Printf("[ERROR] IP authorization failed - forbidden access from %s (expected %s)\n",
				clientIP, cfg.PuqcloudIP)
			if cfg.Debug {
				fmt.Printf("[DEBUG] Client IP details: %s\n", clientIP)
				fmt.Printf("[DEBUG] X-Forwarded-For header: %s\n", c.GetHeader("X-Forwarded-For"))
				fmt.Printf("[DEBUG] X-Real-IP header: %s\n", c.GetHeader("X-Real-IP"))
			}
			c.JSON(http.StatusForbidden, gin.H{
				"status": "error",
				"errors": []string{"Forbidden IP"},
			})
			return
		}

		fmt.Printf("[INFO] IP authorization passed for %s\n", clientIP)

		// Add to proxied list
		fmt.Printf("[INFO] Adding proxy entry to cache for hash: %s\n", req.Hash)
		proxied.Add(req.Hash, req.Token, req.Cookie, req.CSRFPreventionToken, req.URL)

		if cfg.Debug {
			fmt.Printf("[DEBUG] Proxy entry added successfully:\n")
			fmt.Printf("[DEBUG]   Hash: %s\n", req.Hash)
			fmt.Printf("[DEBUG]   Token length: %d characters\n", len(req.Token))
			fmt.Printf("[DEBUG]   Target URL: %s\n", req.URL)
			fmt.Printf("[DEBUG]   Cache operation completed\n")
		}

		fmt.Printf("[INFO] Proxy request processed successfully for %s\n", clientIP)

		// Success response
		c.JSON(http.StatusOK, gin.H{
			"status":  "success",
			"message": "Proxied entry added successfully",
		})

		if cfg.Debug {
			fmt.Printf("[DEBUG] Response sent to client %s with status 200\n", clientIP)
		}
	}
}

func handleVNCWebSocket(cfg *Config, ctx *gin.Context) {
	data := ctx.Param("data")
	fmt.Printf("[INFO] Starting VNC WebSocket connection for data parameter\n")

	if cfg.Debug {
		fmt.Printf("[DEBUG] Received data parameter: %s\n", data)
	}

	token, cookie, csrfp_revention_token, targetURL, err := proxied.Get(data)
	if err != nil {
		fmt.Printf("[ERROR] Failed to decode token and URL from data parameter: %v\n", err)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Data parameter that failed to decode: %s\n", data)
		}
		ctx.String(400, "token and url error: %v", err)
		return
	}

	fmt.Printf("[INFO] Successfully get target URL\n")
	if cfg.Debug {
		fmt.Printf("[DEBUG] Get target URL: %s\n", targetURL)
		fmt.Printf("[DEBUG] Token length: %d characters\n", len(token))
	}

	if err := validateProxmoxURL(targetURL); err != nil {
		fmt.Printf("[ERROR] URL validation failed: %v\n", err)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Invalid URL that failed validation: %s\n", targetURL)
		}
		ctx.String(400, "invalid URL: %v", err)
		return
	}

	fmt.Printf("[INFO] URL validation passed for Proxmox endpoint\n")

	upgrader := websocket.Upgrader{
		CheckOrigin:      func(r *http.Request) bool { return true },
		HandshakeTimeout: 30 * time.Second,
		ReadBufferSize:   8192,
		WriteBufferSize:  8192,
	}

	fmt.Printf("[INFO] Upgrading client connection to WebSocket\n")
	clientConn, err := upgrader.Upgrade(ctx.Writer, ctx.Request, nil)
	if err != nil {
		fmt.Printf("[ERROR] Client WebSocket upgrade failed: %v\n", err)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Upgrade error details: %v\n", err)
		}
		return
	}
	defer clientConn.Close()

	fmt.Printf("[INFO] Client WebSocket connection established successfully\n")
	if cfg.Debug {
		fmt.Printf("[DEBUG] Client connection remote address: %s\n", clientConn.RemoteAddr())
	}

	u, err := url.Parse(targetURL)
	if err != nil {
		fmt.Printf("[ERROR] Failed to parse target URL: %v\n", err)
		if cfg.Debug {
			fmt.Printf("[DEBUG] URL that failed to parse: %s\n", targetURL)
		}
		clientConn.Close()
		return
	}

	dialer := websocket.Dialer{
		TLSClientConfig:  &tls.Config{InsecureSkipVerify: true},
		HandshakeTimeout: 30 * time.Second,
		ReadBufferSize:   8192,
		WriteBufferSize:  8192,
	}

	headers := http.Header{}

	if token != "" {
		headers.Set("Authorization", "PVEAPIToken="+token)
	} else {
		if cookie != "" {
			headers.Set("Cookie", "PVEAuthCookie="+cookie)
		}
		if csrfp_revention_token != "" {
			headers.Set("CSRFPreventionToken", csrfp_revention_token)
		}
	}

	headers.Set("Host", u.Host)
	headers.Set("Origin", "https://"+u.Host)
	headers.Set("User-Agent", "Mozilla/5.0")
	headers.Set("Accept-Encoding", "gzip, deflate, br")
	headers.Set("Accept-Language", "en-US,en;q=0.9")
	headers.Set("Cache-Control", "no-cache")
	headers.Set("Pragma", "no-cache")

	fmt.Printf("[INFO] Connecting to Proxmox backend: %s\n", u.Host)
	if cfg.Debug {
		fmt.Printf("[DEBUG] Full backend URL: %s\n", targetURL)
		fmt.Printf("[DEBUG] Request headers:\n")
		for k, v := range headers {
			// Don't log the full token for security
			if k == "Authorization" {
				fmt.Printf("  %s: PVEAPIToken=***[%d chars]\n", k, len(token))
			} else {
				fmt.Printf("  %s: %v\n", k, v)
			}
		}
	}

	backendConn, resp, err := dialer.Dial(targetURL, headers)
	if err != nil {
		fmt.Printf("[ERROR] Failed to connect to Proxmox backend: %v\n", err)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Backend connection error details: %v\n", err)
			if resp != nil {
				fmt.Printf("[DEBUG] HTTP response status: %s\n", resp.Status)
				fmt.Printf("[DEBUG] Response headers:\n")
				for k, v := range resp.Header {
					fmt.Printf("  %s: %v\n", k, v)
				}
				body, _ := io.ReadAll(resp.Body)
				if len(body) > 0 {
					fmt.Printf("[DEBUG] Response body: %s\n", string(body))
				}
				resp.Body.Close()
			}
		}
		clientConn.Close()
		return
	}
	defer backendConn.Close()

	fmt.Printf("[INFO] Successfully connected to Proxmox backend\n")
	if cfg.Debug && resp != nil {
		fmt.Printf("[DEBUG] Backend connection response status: %s\n", resp.Status)
		fmt.Printf("[DEBUG] Backend response headers:\n")
		for k, v := range resp.Header {
			fmt.Printf("  %s: %v\n", k, v)
		}
	}

	// Clear all deadlines
	clientConn.SetReadDeadline(time.Time{})
	clientConn.SetWriteDeadline(time.Time{})
	backendConn.SetReadDeadline(time.Time{})
	backendConn.SetWriteDeadline(time.Time{})

	if cfg.Debug {
		fmt.Printf("[DEBUG] All connection deadlines cleared\n")
	}

	// Close handlers
	clientConn.SetCloseHandler(func(code int, text string) error {
		fmt.Printf("[INFO] Client connection closing with code %d\n", code)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Client close details: code=%d, text='%s'\n", code, text)
		}
		backendConn.WriteControl(websocket.CloseMessage,
			websocket.FormatCloseMessage(code, text),
			time.Now().Add(time.Second))
		return nil
	})

	backendConn.SetCloseHandler(func(code int, text string) error {
		fmt.Printf("[INFO] Backend connection closing with code %d\n", code)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Backend close details: code=%d, text='%s'\n", code, text)
		}
		clientConn.WriteControl(websocket.CloseMessage,
			websocket.FormatCloseMessage(code, text),
			time.Now().Add(time.Second))
		return nil
	})

	// Ping/pong routine
	fmt.Printf("[INFO] Starting WebSocket keep-alive routine\n")
	pingDone := make(chan struct{})
	var pingOnce sync.Once
	go func() {
		ticker := time.NewTicker(20 * time.Second)
		defer ticker.Stop()

		if cfg.Debug {
			fmt.Printf("[DEBUG] Keep-alive routine started with 20-second intervals\n")
		}

		for {
			select {
			case <-ticker.C:
				if cfg.Debug {
					fmt.Printf("[DEBUG] Sending keep-alive pings\n")
				}

				if err := clientConn.WriteControl(websocket.PingMessage, []byte("client-ping"), time.Now().Add(5*time.Second)); err != nil {
					fmt.Printf("[ERROR] Failed to send client ping: %v\n", err)
					if cfg.Debug {
						fmt.Printf("[DEBUG] Client ping error details: %v\n", err)
					}
					return
				}

				if err := backendConn.WriteControl(websocket.PingMessage, []byte("backend-ping"), time.Now().Add(5*time.Second)); err != nil {
					fmt.Printf("[ERROR] Failed to send backend ping: %v\n", err)
					if cfg.Debug {
						fmt.Printf("[DEBUG] Backend ping error details: %v\n", err)
					}
					return
				}
			case <-pingDone:
				if cfg.Debug {
					fmt.Printf("[DEBUG] Keep-alive routine stopping\n")
				}
				return
			}
		}
	}()

	fmt.Printf("[INFO] Starting WebSocket proxy data forwarding\n")
	errc := make(chan error, 2)
	go proxyWS(clientConn, backendConn, errc, "client->backend", cfg.Debug)
	go proxyWS(backendConn, clientConn, errc, "backend->client", cfg.Debug)

	// Wait for one of the proxy routines to finish
	err2 := <-errc
	pingOnce.Do(func() { close(pingDone) })

	fmt.Printf("[INFO] WebSocket proxy session ending, sending close messages\n")
	if cfg.Debug {
		fmt.Printf("[DEBUG] Sending graceful close messages to both connections\n")
	}

	clientConn.WriteControl(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseNormalClosure, ""), time.Now().Add(time.Second))
	backendConn.WriteControl(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseNormalClosure, ""), time.Now().Add(time.Second))

	if err2 != nil {
		fmt.Printf("[ERROR] WebSocket proxy session ended with error: %v\n", err2)
		if cfg.Debug {
			fmt.Printf("[DEBUG] Proxy error details: %v\n", err2)
		}
	} else {
		fmt.Printf("[INFO] WebSocket proxy session completed successfully\n")
		if cfg.Debug {
			fmt.Printf("[DEBUG] Both proxy routines finished without errors\n")
		}
	}
}
