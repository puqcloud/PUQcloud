package main

import (
	"fmt"
	"sync"
	"time"
)

// ProxiedItem stores Proxmox credentials for a proxied URL
type ProxiedItem struct {
	Token               string
	Cookie              string
	CSRFPreventionToken string
	URL                 string
	timer               *time.Timer
}

// ProxiedList is a thread-safe in-memory list of proxied URLs
type ProxiedList struct {
	data sync.Map
	ttl  time.Duration
}

// NewProxiedList creates a list with a given TTL
func NewProxiedList(ttl time.Duration) *ProxiedList {
	return &ProxiedList{ttl: ttl}
}

// Add stores an item with auto-deletion after TTL
func (pl *ProxiedList) Add(key, token, cookie, csrfp_revention_token, url string) {
	// Stop old timer if key exists
	if old, ok := pl.data.Load(key); ok {
		oldItem := old.(*ProxiedItem)
		oldItem.timer.Stop()
	}

	// Create new item
	item := &ProxiedItem{
		Token:               token,
		Cookie:              cookie,
		CSRFPreventionToken: csrfp_revention_token,
		URL:                 url,
	}

	// Timer to delete the key after TTL
	item.timer = time.AfterFunc(pl.ttl, func() {
		pl.data.Delete(key)
	})

	pl.data.Store(key, item)
}

// Get retrieves an item, returns nil if not found
func (pl *ProxiedList) Get(key string) (string, string, string, string, error) {
	if v, ok := pl.data.Load(key); ok {
		item := v.(*ProxiedItem)
		return item.Token, item.Cookie, item.CSRFPreventionToken, item.URL, nil
	}
	return "", "", "", "", fmt.Errorf("key %s not found", key)
}

// Remove deletes an item manually
func (pl *ProxiedList) Remove(key string) {
	if v, ok := pl.data.Load(key); ok {
		item := v.(*ProxiedItem)
		item.timer.Stop()
		pl.data.Delete(key)
	}
}

func (pl *ProxiedList) List() map[string]ProxiedItem {
	snapshot := make(map[string]ProxiedItem)

	pl.data.Range(func(key, value interface{}) bool {
		k := key.(string)
		v := value.(*ProxiedItem)
		snapshot[k] = ProxiedItem{
			Token:               v.Token,
			Cookie:              v.Cookie,
			CSRFPreventionToken: v.CSRFPreventionToken,
			URL:                 v.URL,
			timer:               nil,
		}
		return true
	})
	return snapshot
}
