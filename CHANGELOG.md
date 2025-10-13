# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0-beta.4] - 2025-10-13
### Added
- DNS Manager
- PowerDNS Module
- Available create own module task queues
- Module log function is follow global log level
- Module log, Delete all function and button
- Task Queue: Task Detail more detailed

---

## [1.0.0-beta.3] - 2025-09-28
### Added
- Proxmox Module 

### Fixed
- puqMonobank 
- puqTraccarSMS

---

## [1.0.0-beta.1] - 2025-07-03
### Added
- Added new options to the puqcloud:demo-data command: create/pay proformas, deploy/terminate services.
- Added puqcloud:create-client command:
- Supports parameterized client creation with validation.
- Required: firstname, email, password, address1, city, postcode, country, region.
- Optional: lastname, company, tax-id, language, status, address2, phone, notes, admin-notes, credit-limit.
- Validates unique email/company/tax-id and country-region compatibility.
- Auto-generates full client structure (User, Client, ClientAddress).
- Supports balance top-up via --extrapay.
- Added detailed help with examples.
- Improved payment success/failure messages and error handling in client creation.

### Fixed
- gitignore
- admin templates
- fixed UUID generation issue during client creation.

## [1.0.0-beta] - 2025-07-01
### Added
- First public beta release.
- Initial modular architecture.
- Admin and client panels.
- User and service management system.
- Billing, invoicing.
