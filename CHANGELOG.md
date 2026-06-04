# Changelog

All notable changes to this project will be documented in this file.


---
## [Unreleased]

### Changed

- **Project ownership transfer**: effective **2026-06-01**, ownership of PUQcloud and all associated copyright was transferred from **PUQ sp. z o.o.** (Poland) to **PUQ Software** (Winnipeg, Manitoba, Canada). Every source-file copyright header, `LICENSE`, the `composer.json` author entry and `README.md` were updated from `Copyright (C) 2025 PUQ sp. z o.o.` to `Copyright (C) 2025-2026 PUQ Software`. The software remains licensed under the **GNU GPLv3** with unchanged terms; the contact e-mail (`support@puqcloud.com`) and website (`https://puqcloud.com`) are unchanged. See the [`NOTICE`](NOTICE) file for the full transfer record.


---
## [1.5.0] – 2026-04-11

### Added

- **Ticket system**: full admin UI (departments, statuses, canned responses, ticket list with filters, SLA tracking, activity timeline with replies), client reopen with `allow_client_reopen` toggle, admin reopen notification, SLA breach reminder command, auto-close command, email import command. New notification templates: `ticket_sla_breached`, `ticket_closed`, `ticket_reopened`.
- **Invoice billing (per-service mode)**: flexible `billing_mode` (`prepaid`/`invoice`/`both`) at product and per-service level with switch button on client area, aggregated invoices, reminder emails, empty-invoice cleanup cron + command, invoice cleanup on pending→cancelled.
- **Standalone invoices**: full CRUD with draft↔unpaid lifecycle, resend notification, due date editing, dropdown Actions UI.
- **Upgrade/downgrade machinery**: proration, billing purposes, product-level `allow_upgrade` / `allow_downgrade` / `allow_proration_alignment` opt-in flags, upgrade/downgrade template system, prepaid and invoice-mode support.
- **Service views `default` flow**: new shared `ServiceViewManageActions` trait (`SetLabel`, `TerminationRequest`, `CancelTerminationRequest`, `SwitchBillingMode`, `GetStatus`), complete `default` order/manage/upgrade/proration flow with `awaiting_payment`, `pending`, `suspended`, `terminated` sub-views. Module `ManageController`s now `use` the trait and add only module-specific endpoints on top.
- **puqVPNCP module**: full rewrite of order / manage / upgrade / proration controllers and views, new `awaiting_payment` view, bandwidth-as-string enforcement, `SyncServers` command overhaul, license validation visible on admin area, new location picker via shared `ProductGroup::getProductDataOptions()`.
- **puqProxmox Product Options admin tool**: new sidebar page to create every `product_option_group` / `product_option` the module expects under stable keys. "Create Missing" button, dynamic generation of location options from `puq_pm_cluster_groups` (value format `{country_code}-{data_center}`) and OS options from `puq_pm_lxc_os_templates`, tiered CPU/RAM/disk options storing the delta over a product base entered on the page header with localStorage persistence and out-of-sync drift detection.
- **Product pages**: new "Settings" tab on product and product_group pages (basic / billing configuration / upgrade-downgrade rules / inventory / visibility / service-view templates) split off from the old General tab.
- **Admin controllers split**: `AdminProductsController` (≈3000 lines) split into `AdminProductAttributesController`, `AdminProductGroupsController`, `AdminProductOptionsController`. Ten controllers added for Clients + Finance refactor, 29 routes documented.
- **Client tabs**: new Notifications tab (by users) and Activity Log tab (by client/services/invoices).
- **API / Swagger**: API documentation overhaul, `AdminSwagger` renamed to `Swagger/Admin`, new `Swagger/Client` namespace with puqProxmox Client swagger.
- **UI helper**: `renderEditLinkBlank()` helper for edit buttons that open in a new tab.

### Changed

- **Edit buttons → real links**: `renderEditButton` replaced with `renderEditLink` in 24 admin / module DataTables. Middle-click, Ctrl+click and right-click "open in new tab" now work natively on every edit button. Five dead `renderEditButton` branches removed where the backend never populated `urls.edit`. Two `window.open(_blank)` patterns converted to `renderEditLinkBlank`.
- **puqProxmox `waitForTask`**: now accepts `exitstatus` starting with `WARNINGS` as success (matches Proxmox task exit contract). Unblocks LXC deploy when Proxmox emits the systemd-252 nesting warning during `vzcreate`. Warnings are logged via `logModule` with upid/vmid/node/lxc_instance_uuid so they aren't silently swallowed.
- **puqProxmox LXC create flow**: `features` (nesting, fuse, keyctl, mknod, mount_nfs, mount_cifs) applied AFTER container creation via `pvesh set` over SSH instead of the HTTP API POST, because API tokens cannot set these privileged parameters.
- **Invoice / Service / Product / ProductGroup / Client models**: extended with upgrade/downgrade, proration, billing_mode switching, and service lifecycle helpers (~2000 lines of new shared logic).
- **Delete confirmation**: unified delete modal pattern across admin tables.
- **Console commands**: normalized signatures across `Finance:*`, `Service:*`, `SslManager:*`, `System:*`, `Tickets:*`; new `TicketsAutoClose`, `TicketsImportEmails`, `TicketsSlaReminder`, `FinanceCleanupEmptyInvoices`.

### Fixed

- **puqProxmox LXC nesting deploy**: was silently failing with `exitstatus = "WARNINGS: 1"` from `vzcreate` before `setLxcConfig` could apply `features: nesting=1`. Fix in `waitForTask` above resolves the classic catch-22 (features stripped to be applied later, but warning about missing features killed the flow first).
- **puqProxmox `getScriptLogList`**: guarded against orphaned script logs whose `PuqPmScript` or `PuqPmScript->relatedModel` morphTo is `null` (was throwing "Attempt to read property 'name' on null" in the LXC service view when a parent preset / template had been deleted).
- **Desloppify audit (15 fixes)**: dead code removal, type safety, security hardening, enum usage. Health score 66.9/100.
- **Billing audit (13 fixes)**: idempotent seeders, `puqcloud:seed` safe to rerun, race-condition hardening.
- **Controller refactoring**: cleanup of leftover references and OA annotations after the product controllers split.

---
## [1.4.0] – 2026-01-20

### Added

- Admin API KEY
- Admin API Documentation
- Client API Documentation

---
## [1.3.0] – 2025-12-15

### Added

- Proxmox Module (v1.0.1)
    - Environment Variables Cluster Group level
    - Environment Variables Cluster level
    - Environment Variables LXC Preset level
    - LXC Instances Page

---

## [1.2.0] – 2025-12-10

### Added

- Indexes in tables transactions and clients
- Proxmox Module (v1.0.1)
    - LXC deploy progress and monitoring
    - LXC controller function security protection by deploy status
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
    - New Scheduler task: "Deploy Web Proxy configuration on all load balancer nodes based on all successfully deployed
      app instances."

### Fixed

- Proxmox Module
    - Suspend/Unsuspend

---

## [1.0.0] – 2025-12-01

### First Stable Release

This is the first stable release of **PUQcloud**, including full automation, billing, DNS, Proxmox support, SaaS
deployment, monitoring, and payment integrations.

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
