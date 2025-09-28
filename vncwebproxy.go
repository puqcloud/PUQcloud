package main

import (
	"fmt"
	"net/url"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/gorilla/websocket"
)

const Version = "1.0.1"

func proxyWS(src, dst *websocket.Conn, errc chan<- error, label string, debug bool) {
	fmt.Printf("[INFO] Starting WebSocket proxy routine: %s\n", label)

	defer func() {
		if r := recover(); r != nil {
			fmt.Printf("[ERROR] %s panic occurred: %v\n", label, r)
			if debug {
				fmt.Printf("[DEBUG] %s panic stack trace available\n", label)
			}
			errc <- fmt.Errorf("panic: %v", r)
		}
		fmt.Printf("[INFO] WebSocket proxy routine finished: %s\n", label)
	}()

	messageCount := 0
	totalBytes := int64(0)

	for {
		mt, msg, err := src.ReadMessage()
		if err != nil {
			if websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseNoStatusReceived, websocket.CloseGoingAway, websocket.CloseAbnormalClosure) {
				fmt.Printf("[INFO] %s connection closed normally after %d messages (%d bytes total)\n",
					label, messageCount, totalBytes)
				if debug {
					fmt.Printf("[DEBUG] %s close error details: %v\n", label, err)
				}
				errc <- nil
				return
			}

			fmt.Printf("[ERROR] %s read error after %d messages: %v\n", label, messageCount, err)
			if debug {
				fmt.Printf("[DEBUG] %s read error details: %v\n", label, err)
				fmt.Printf("[DEBUG] %s statistics: messages=%d, bytes=%d\n", label, messageCount, totalBytes)
			}
			errc <- err
			return
		}

		messageCount++
		totalBytes += int64(len(msg))

		if debug && len(msg) > 0 {
			msgType := "unknown"
			if len(msg) >= 12 && string(msg[:3]) == "RFB" {
				msgType = "RFB_handshake"
			} else if len(msg) >= 4 {
				switch msg[0] {
				case 0:
					msgType = "FramebufferUpdate"
				case 1:
					msgType = "SetColourMapEntries"
				case 2:
					msgType = "Bell"
				case 3:
					msgType = "ServerCutText"
				default:
					if strings.Contains(label, "client->backend") {
						switch msg[0] {
						case 0:
							msgType = "SetPixelFormat"
						case 2:
							msgType = "SetEncodings"
						case 3:
							msgType = "FramebufferUpdateRequest"
						case 4:
							msgType = "KeyEvent"
						case 5:
							msgType = "PointerEvent"
						case 6:
							msgType = "ClientCutText"
						}
					}
				}
			}

			fmt.Printf("[DEBUG] %s message #%d: ws_type=%d, length=%d, vnc_type=%s, first_bytes=%v\n",
				label, messageCount, mt, len(msg), msgType, msg[:min(len(msg), 16)])
		}

		// Log significant message milestones at info level
		if messageCount%1000 == 0 {
			fmt.Printf("[INFO] %s processed %d messages (%d bytes total)\n",
				label, messageCount, totalBytes)
		}

		if err := dst.WriteMessage(mt, msg); err != nil {
			if websocket.IsCloseError(err, websocket.CloseNormalClosure, websocket.CloseNoStatusReceived, websocket.CloseGoingAway, websocket.CloseAbnormalClosure) {
				fmt.Printf("[INFO] %s write connection closed normally after %d messages\n",
					label, messageCount)
				if debug {
					fmt.Printf("[DEBUG] %s write close details: %v\n", label, err)
					fmt.Printf("[DEBUG] %s final statistics: messages=%d, bytes=%d\n",
						label, messageCount, totalBytes)
				}
				errc <- nil
				return
			}

			fmt.Printf("[ERROR] %s write error after %d messages: %v\n", label, messageCount, err)
			if debug {
				fmt.Printf("[DEBUG] %s write error details: %v\n", label, err)
				fmt.Printf("[DEBUG] %s statistics at error: messages=%d, bytes=%d\n",
					label, messageCount, totalBytes)
			}
			errc <- err
			return
		}

		// Debug log for write success on significant messages
		if debug && (messageCount <= 10 || messageCount%100 == 0) {
			fmt.Printf("[DEBUG] %s successfully wrote message #%d (%d bytes)\n",
				label, messageCount, len(msg))
		}
	}
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

func validateProxmoxURL(targetURL string) error {
	u, err := url.Parse(targetURL)
	if err != nil {
		return fmt.Errorf("invalid URL: %v", err)
	}

	if u.Scheme != "wss" && u.Scheme != "ws" {
		return fmt.Errorf("invalid scheme: %s, expected ws or wss", u.Scheme)
	}

	if !strings.Contains(u.Path, "/vncwebsocket") {
		return fmt.Errorf("invalid path: %s, expected VNC websocket path", u.Path)
	}

	return nil
}

func main() {

	// Parse CLI flags
	cfg := ParseFlags()

	// Example usage of parsed config
	fmt.Println("PUQcloud IP:", cfg.PuqcloudIP)
	fmt.Println("API Key:", cfg.ApiKey)
	fmt.Println("Port:", cfg.Port)
	fmt.Println("Debug:", cfg.Debug)

	gin.SetMode(gin.ReleaseMode)
	r := gin.Default()

	r.POST("/api/proxy", proxyHandler(cfg))

	r.GET("/vncproxy/:data", func(ctx *gin.Context) {
		handleVNCWebSocket(cfg, ctx)
	})

	fmt.Println("[INFO] Starting server on :8080")
	r.Run(fmt.Sprintf(":%d", cfg.Port))
}
