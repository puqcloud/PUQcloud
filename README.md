# PUQ Cloud Panel

**PUQ Cloud Panel** is an open-source, modular cloud automation and billing system designed to help individuals and companies launch their own IT service business quickly and independently.  
Built on [Laravel](https://laravel.com), it includes full billing, provisioning, DNS, cluster management, SaaS deployment, and cloud orchestration — all in one platform.

🌐 [Official Website](https://puqcloud.com) | 📚 [Documentation](https://doc.puq.info/books/puqcloud-panel)

---

## 🧩 Key Features

- ✅ **Modular Architecture** — Easily extendable with custom modules.
- 🚀 **Service Automation** — Automatic deployment and full lifecycle management.
- 💳 **Advanced Billing System** — Invoices, proformas, credit balance, multi-currency, tax by region.
- 🛍️ **Product Catalog & eCommerce** — Sell hosting, SaaS, LXC, services, and physical items.
- 🛠️ **Helpdesk & Support Tools** — Free or paid support (Remote Hands).
- 🧾 **Multi-company Support** — Multiple home companies with separate tax profiles.
- 🌍 **Internationalization** — Taxes, currencies, languages per client country.
- 🏗️ **Cluster Architecture**
    - Master nodes management
    - Worker nodes
    - Secure token authorization
    - Automatic syncing
- 🐳 **APP Hosting (SaaS)** — Deploy SaaS applications on Proxmox clusters automatically.
- 📦 **LXC Hosting** — Provision LXC containers as products.
- 🔄 **Queue-Based Task Engine** — Fast background operations.
- 🗄️ **DNS Manager** — PowerDNS, HestiaCP, zone migration.
- 🔐 **SSL Manager** — Let’s Encrypt, ZeroSSL, ACME, EAB support.
- 🔓 **Free & Open Source**

---

## 🎯 Mission

We aim to make cloud business infrastructure available to everyone by providing a powerful, free system for building your IT business.

> 🫶 Learn more about our vision and philosophy  
> https://puqcloud.com/puqcloud-panel.php

---

## 👥 Community & Contribution

Community Platform → https://community.puqcloud.com/

PUQcloud Panel is developed by international volunteers.

- Submit code → Fork and make a PR
- Join the project → https://puqcloud.com/puqcloud-volunteers.php
- Support development → https://puqcloud.com/puqcloud-sponsors.php

We welcome ideas, bug reports, translations, and module developers.

---

## ⚙️ Requirements

- PHP 8.2+
- MySQL 8.x / MariaDB
- Redis (queues)
- Node.js (frontend assets)
- Composer
- npm

---

## 🔌 Proxmox Integration Features

PUQcloud provides full automation for Proxmox clusters:

### **LXC Hosting**
- Create, start, stop, reboot LXC containers
- Automatic provisioning and termination
- Custom resource limits
- Reverse/forward DNS automation

### **APP Hosting (SaaS Apps)**
- Automatic SaaS deployment on Proxmox
- Each app runs in isolated environment
- Multi-cluster support
- Load balancing and rebalance tools
- Automatic syncing between clusters

### **Console Access**
#### Required modules:

#### **noVNC Proxy (for console access of servers and LXC)**
Repository:  
🔗 https://github.com/puqcloud/webproxy

#### **VNC Web Proxy (for APP deployments on Proxmox)**
Repository:  
🔗 https://github.com/puqcloud/vncwebproxy

These tools allow secure, modular console access for both LXC and APP containers.

---

## 🛠️ Fully Automated Installation (Production)

Ready-to-use scripts for installing the PUQcloud Panel:  
👉 https://github.com/puqcloud/PUQcloud-Scripts

---

## 🚀 Installation (Development)

```bash
git clone https://github.com/puqcloud/PUQcloud.git
cd PUQcloud
composer install
cp .env.example .env
php artisan key:generate
chmod -R 775 storage bootstrap/cache
npm install
npm run prod

```
Edit the .env file and fill in the required variables (database, app URL, etc).

Then run this command to create an admin user:
```bash
php artisan puqcloud:seed --email admin@example.com --password QWEqwe123 --name Myname
```





## License

PUQcloud is open-source software licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

## Authors

**Ruslan Polovyi** — founder and lead developer at **PUQ sp. z o.o.**

**Dmytro Kravchenko** — Developer / DevOps engineer
