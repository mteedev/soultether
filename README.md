# 💕 SoulTether — OpenSim Partner System for WordPress

**SoulTether** is a WordPress plugin that brings a fully-featured avatar partnership system to OpenSimulator grids. Residents can send, accept, decline, withdraw, and untether partnerships directly from your WordPress site — with changes syncing live to the OpenSim Robust database and appearing on in-world profiles.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Version](https://img.shields.io/badge/Version-1.0.1-green.svg)](https://github.com/mteedev/soultether/releases)

---

## ✨ Features

- 💌 **Send tether requests** — with optional personal message
- ✅ **Accept or Decline** incoming requests
- ↩️ **Withdraw** outgoing pending requests
- 💔 **Untether** active partnerships
- ❤️ **Live sync** to OpenSim Robust database (`userprofile.profilePartner`)
- 👥 **Friend list** pulled live from Robust DB (`Friends` table)
- 📬 **Email notifications** to the request recipient
- 🛡️ **Security** — self-partner prevention, ownership validation, nonce protection
- 🗂️ **Admin panel** — view all requests with status filters and pagination
- 📱 **Responsive** — works on mobile and desktop

---

## ⚙️ Requirements

- WordPress 6.0+
- PHP 8.0+
- [w4os — WordPress for OpenSimulator](https://wordpress.org/plugins/w4os/) plugin
- Direct MySQL access to your OpenSim Robust database
- OpenSimulator grid with standard `userprofile`, `UserAccounts`, and `Friends` tables

---

## 🚀 Installation

1. Download the latest release zip from [Releases](https://github.com/mteedev/soultether/releases)
2. In WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip and activate
4. **Configure your Robust DB credentials** in `soultether.php` (see Configuration below)
5. Create a WordPress page and add the shortcode `[soultether]`
6. Add the page to your navigation menu

---

## 🔧 Configuration

Open `soultether.php` and set your Robust database credentials:

```php
// Option 1: Unix socket (recommended if WordPress and Robust are on the same server)
define( 'SOULTETHER_ROBUST_SOCKET', '/run/mysqld/mysqld.sock' );
define( 'SOULTETHER_ROBUST_DB',     'robust' );
define( 'SOULTETHER_ROBUST_USER',   'your_db_user' );
define( 'SOULTETHER_ROBUST_PASS',   'your_db_password' );

// Option 2: TCP connection (if Robust DB is on a different server)
define( 'SOULTETHER_ROBUST_SOCKET', '' );  // leave blank to use TCP
define( 'SOULTETHER_ROBUST_HOST',   '127.0.0.1' );
define( 'SOULTETHER_ROBUST_DB',     'robust' );
define( 'SOULTETHER_ROBUST_USER',   'your_db_user' );
define( 'SOULTETHER_ROBUST_PASS',   'your_db_password' );
```

> **Security tip:** Move your credentials to `wp-config.php` to keep them out of the plugin files.

---

## 📋 Database Schema Compatibility

SoulTether is built against the standard OpenSimulator Robust database schema:

| Table | Columns Used |
|---|---|
| `UserAccounts` | `PrincipalID`, `FirstName`, `LastName` |
| `userprofile` | `useruuid` (PK), `profilePartner` |
| `Friends` | `PrincipalID`, `Friend` |

If your grid uses different column names, adjust the queries in `includes/class-robust-db.php`.

---

## 🎯 Shortcode

Place this shortcode on any WordPress page:

```
[soultether]
```

---

## 📁 File Structure

```
soultether/
├── soultether.php              ← Main plugin file
├── includes/
│   ├── class-robust-db.php     ← Robust DB connection & queries
│   ├── class-partner-db.php    ← WordPress request tracking table
│   ├── class-partner-core.php  ← Business logic
│   └── helpers.php             ← Template utilities
├── templates/
│   ├── dashboard.php           ← Main shortcode view
│   ├── admin-panel.php         ← WP Admin panel
│   ├── not-logged-in.php       ← Guest view
│   └── no-avatar.php           ← No avatar linked view
└── assets/
    ├── soultether.css          ← Frontend styles
    └── soultether.js           ← AJAX interactions
```

---

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md)

---

## 👤 Author

**Gundahar Bravin**
[nerdypappy.com](https://nerdypappy.com)
GitHub: [@mteedev](https://github.com/mteedev)

---

## 📜 License

GPL-2.0 — see [LICENSE](LICENSE) for details.

This plugin was built for [Neverworld Grid](https://neverworldgrid.com) and released to the OpenSim community.
