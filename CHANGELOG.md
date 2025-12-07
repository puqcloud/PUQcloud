# Changelog

All notable changes to this project will be documented in this file.

---
## [1.1.1] – 2025-12-07


### Added
- Proxmox Module
    - App: client area buttons "Reboot" and "Proxy Reload"
    - App: admin area button "Reboot LXC"
---

## [1.1.0] – 2025-12-05

### Changed
- Scheduler UI: redesigned display, now shown in a row layout.

### Added
- Proxmox Module
    - New Scheduler task: "Deploy Web Proxy configuration on all load balancer nodes based on all successfully deployed app instances."

### Fixed
- Proxmox Module
    - Suspend/Unsuspend

---
## [1.0.0] – 2025-12-01
### First Stable Release
This is the first stable release of **PUQcloud**, including full automation, billing, DNS, Proxmox support, SaaS deployment, monitoring, and payment integrations.

---

## Main Features

### **Dashboard, Monitoring & Logs**
- System status widgets
- Task Queue monitoring
- Module logs with log

### **Automation & Access Control**
- Full task queue system
- Custom queues for modules
- Automatic Scheduler Tasks
- Multi-company support
- Role & permission system

### **Proxmox Module**
- Fully included in the stable release
- Sell **LXC containers** as standalone products
- Sell **APP (SaaS) applications** with automatic deploy
- Each app deploys in isolated environments
- Multi-cluster support
- Http load balancing
- Automatic forward & reverse DNS records
- Automatic deploy of SaaS apps
- Apps run in containers in LXC
- Local IP per app (WEB proxy)
- APP templates

### **DNS System**
- DNS Manager
- PowerDNS module
- HestiaCP DNS module
- Zone migration between DNS server groups
- DNS record validation

### **SSL & Security**
- SSL Manager
- ACME module
- Let’s Encrypt & ZeroSSL
- ZeroSSL EAB support

### **Admin & Client Panel**
- Full billing & invoice system
- Service management
- Multi-language system
- Enhanced client and admin templates

### Payment Modules
- **Stripe**
- **PayPal**
- **Monobank**
- **Przelewy24**
- **Bank Transfer**

--- 
### **CLI Tools**
#### **puqProxmox**
- **puqProxmox:LoadBalancerRebalance** – Rebalances all active Proxmox load balancers and DNS records.
- **puqProxmox:MakeBackups** – Run backup tasks for Proxmox.
- **puqProxmox:SyncApp** – Synchronize APP data to the system.
- **puqProxmox:SyncClusters** – Synchronize cluster data to the system.

#### **puqSamplePlugin**
- **puqSamplePlugin:test** – Test command for Sample Plugin.

#### **puqSampleProduct**
- **puqSampleProduct:test** – Test command for Sample Product.

#### **puqcloud**
- **puqcloud:create-client** – Create a new client with full profile and billing address.
- **puqcloud:demo_seed** – Generate realistic demo data with products, clients, and groups.
- **puqcloud:regions** – Show all supported regions.
- **puqcloud:seed** – Run Post-Install Seed.

#### **Finance**
- **Finance:ChargeServices** – Charge active services and create transaction records.

#### **Products**
- **Products:ConvertPrice** – Convert product prices using currency exchange rate.

#### **Schedule**
- **Schedule:listCommandsJson** – Show scheduled commands list in JSON format.

#### **Service**
- **Service:CancellationServices** – Cancel pending services when funds are not enough.
- **Service:CreateServices** – Create pending services after successful payment.
- **Service:SuspendServices** – Suspend active services with insufficient funds.
- **Service:TerminationServices** – Terminate suspended services with insufficient funds.
- **Service:UnsuspendServices** – Unsuspend services when funds become sufficient.

#### **SslManager**
- **SslManager:CheckExpiration** – Check certificates and mark expired ones.
- **SslManager:ProcessPending** – Process and issue pending certificates.
- **SslManager:ProcessRenewal** – Renew active certificates when needed.

#### **System**
- **System:Cleanup** – Clear logs, sessions, and history data.
- **System:DeleteAllTasks** – Delete all tasks from the system.
- **System:clearingLostTasks** – Mark lost tasks as failed and remove duplicates.
- **System:queueTest** – Run test tasks on all queues for 30 seconds.
