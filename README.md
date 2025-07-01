# PUQ Cloud Panel

**PUQ Cloud Panel** is an open-source, modular cloud automation and billing system designed to empower individuals and companies to start their IT service business quickly and independently. Built on [Laravel](https://laravel.com), it provides advanced billing, provisioning, service management, and cloud orchestration features — all in a single system.

🌐 [Official Website](https://puqcloud.com) | 📚 [Documentation](https://doc.puq.info/books/puqcloud-panel)

---

## 🧩 Key Features

- ✅ **Modular Architecture** — Easily extendable with custom modules.
- 🚀 **Service Automation** — Automatically deploy and manage cloud services.
- 💳 **Advanced Billing System** — Invoices, proformas, add funds, taxation by region.
- 🛍️ **Product Catalog & eCommerce** — Sell services and physical items.
- 🛠️ **Helpdesk & Support** — Manage paid or free technical support (Remote Hands).
- 🧾 **Multi-company Support** — Manage multiple home companies under one panel.
- 🌍 **Internationalization** — Tax rules, currencies, languages per client country.
- 🏗️ **Cluster Support** — Node orchestration with master/agent communication.
- 🔄 **Queue-Based Task Handling** — Fast and reliable job processing in background.
- 🔓 **Free & Open Source**

---

## 🎯 Mission

Our mission is to **democratize cloud business infrastructure** by giving everyone the tools to run their own IT business — for free.


> 🫶 Learn more about our [goals and philosophy](https://puqcloud.com/puqcloud-panel.php)

---

## 👥 Community & Contribution

PUQ Cloud Panel is built and maintained by a global community of volunteers.

- 💡 Want to contribute code? Fork the repo and submit a PR.
- 🤝 Want to help in other ways? Join as a [Volunteer](https://puqcloud.com/puqcloud-volunteers.php)
- 💰 Want to support us? Become a [Sponsor](https://puqcloud.com/puqcloud-sponsors.php)

We welcome all types of contributions — code, documentation, translations, bug reports, and ideas.

---

## ⚙️ Requirements

- PHP 8.2 or higher
- Laravel 12+
- MySQL 8.x or MariaDB
- Redis (for queues)
- Node.js (for frontend assets, optional)
- Composer
- npm

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
