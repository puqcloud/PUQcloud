# vncwebproxy

`vncwebproxy` is a Go app to proxy traffic between clients and your Proxmox server in **PUQcloud**.

## Requirements
- Go installed.
- noVNC v1.3.0 in `/var/www/html` ([download](https://github.com/novnc/noVNC/releases/tag/v1.3.0)).

## Compile
```bash
go build -o vncwebproxy
```

## Run
```bash
./vncwebproxy -puqcloud_ip=<PUQCLOUD_IP> -api_key=<API_KEY> [-port=8080] [-debug] [-v]
```
- `-puqcloud_ip` (required) — PUQcloud IP  
- `-api_key` (required) — API key  
- `-port` (optional, default 8080)  
- `-debug` (optional)  
- `-v` — show version  

Example:
```bash
./vncwebproxy -puqcloud_ip=77.87.125.211 -api_key=QWEqwe123 -port=8080 -debug
```

## Nginx SSL config
```nginx
server {
    listen 80;
    server_name novnc-dev.puqcloud.com;
    return 301 https://$host$request_uri;
}
server {
    listen 443 ssl;
    server_name novnc-dev.puqcloud.com;

    root /var/www/html;
    index vnc.html;

    ssl_certificate /etc/letsencrypt/live/novnc-dev.puqcloud.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/novnc-dev.puqcloud.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location /vncproxy {
        proxy_pass http://127.0.0.1:8080/vncproxy;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }

    location / { try_files $uri $uri/ =404; }
}
```
