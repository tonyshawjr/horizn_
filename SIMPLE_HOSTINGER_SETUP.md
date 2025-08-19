# 🚀 SIMPLE Hostinger Setup - Everything Flat

## Just Upload Everything to public_html/horizn/

```
public_html/
└── horizn/
    ├── app/            (entire app folder)
    ├── database/       (entire database folder)
    ├── assets/         (from public/assets)
    ├── index.php       (from public)
    ├── i.php          (from public)
    ├── h.js           (from public)
    ├── data.js        (from public)
    ├── .htaccess      (from public)
    └── .env           (create this)
```

That's it. Everything at the same level.

---

## Step 1: Fix ONE Path in index.php

Edit line 5 of `index.php`:
```php
// Change from:
require_once __DIR__ . '/../app/config/app.php';

// To:
require_once __DIR__ . '/app/config/app.php';
```

---

## Step 2: Create Your .env

Create `.env` in `/public_html/horizn/`:
```
APP_URL=https://horizn.yourdomain.com
DB_HOST=localhost
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

---

## Step 3: Import Database

1. Create database in Hostinger panel
2. Go to phpMyAdmin
3. Import `database/schema.sql`

---

## That's It!

Visit `https://horizn.yourdomain.com` and you'll see the setup page.

No complicated path fixes. No multiple files to edit. Just upload, fix one line, done.