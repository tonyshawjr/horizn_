# 🚀 Hostinger Deployment Guide for horizn_ Analytics

## Prerequisites
- Hostinger hosting account with PHP 7.4+ and MySQL 8.0+
- FTP/File Manager access
- Database creation privileges

---

## Step 1: Prepare Files for Upload

### Files to Upload:
```
✅ /app/        → upload to root (not public_html)
✅ /database/   → upload to root (not public_html)
✅ /public/     → contents go INTO public_html
✅ .env.example → upload to root
```

### Files to SKIP:
```
❌ .git/
❌ .serena/
❌ /horizn-wp-plugin/ (unless installing on WordPress)
❌ /context/
❌ CLAUDE.md files
```

---

## Step 2: Database Setup

### 2.1 Create Database in Hostinger
1. Login to Hostinger Panel (hPanel)
2. Go to **Databases → MySQL Databases**
3. Click **Create New Database**
4. Note down:
   - Database name: `u123456789_horizn`
   - Username: `u123456789_horizn`
   - Password: `[your-secure-password]`
   - Host: `localhost` (usually)

### 2.2 Import Database Schema
1. Click **phpMyAdmin** next to your database
2. Select your database
3. Click **Import** tab
4. Upload `/database/schema.sql`
5. Click **Go**
6. Repeat for `/database/migrations.sql`
7. (Optional) Import `/database/seed.sql` for test data

---

## Step 3: File Structure Setup

Your Hostinger file structure should look like:

```
/home/u123456789/
├── domains/
│   └── yourdomain.com/
│       ├── public_html/        (web root)
│       │   ├── index.php       (from /public/)
│       │   ├── .htaccess       (from /public/)
│       │   ├── h.js            (from /public/)
│       │   ├── i.php           (from /public/)
│       │   ├── data.js         (from /public/)
│       │   └── assets/         (from /public/assets/)
│       ├── app/                (entire /app/ folder)
│       ├── database/           (entire /database/ folder)
│       └── .env               (create from .env.example)
```

### 3.1 Upload via File Manager
1. Go to **Files → File Manager** in hPanel
2. Navigate to `domains/yourdomain.com/`
3. Upload `/app/` and `/database/` folders here
4. Navigate to `public_html/`
5. Upload contents of `/public/` folder (NOT the folder itself)

### 3.2 Fix File Paths
Edit `public_html/index.php` line 5:
```php
// Change from:
require_once __DIR__ . '/../app/config/app.php';

// To:
require_once dirname(__DIR__) . '/app/config/app.php';
```

---

## Step 4: Environment Configuration

### 4.1 Create .env File
1. In File Manager, go to `domains/yourdomain.com/`
2. Create new file called `.env`
3. Add this configuration:

```env
# App Settings
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false
APP_KEY=generate-32-char-random-string-here

# Database (from Step 2.1)
DB_HOST=localhost
DB_DATABASE=u123456789_horizn
DB_USERNAME=u123456789_horizn
DB_PASSWORD=your-database-password
DB_PORT=3306

# Email Settings (for magic links)
MAIL_DRIVER=mail
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="horizn_ Analytics"

# Optional: Use SMTP for better deliverability
# MAIL_DRIVER=smtp
# MAIL_HOST=smtp.hostinger.com
# MAIL_PORT=587
# MAIL_USERNAME=noreply@yourdomain.com
# MAIL_PASSWORD=your-email-password
# MAIL_ENCRYPTION=tls

# Security
SESSION_LIFETIME=120
MAGIC_LINK_LIFETIME=15
PEPPER=change-this-to-random-string

# Tracking
TRACKING_COOKIE_NAME=h_uid
TRACKING_SESSION_TIMEOUT=1800
```

### 4.2 Set Correct Permissions
In File Manager, right-click and set permissions:
```
/app/            → 755
/database/       → 755
.env             → 644
public_html/     → 755
```

---

## Step 5: Initial Setup

### 5.1 Run Database Migrations
1. Create a file `migrate.php` in public_html:
```php
<?php
require_once dirname(__DIR__) . '/app/config/app.php';
require_once dirname(__DIR__) . '/database/migrate.php';
echo "Migration complete!";
```

2. Visit: `https://yourdomain.com/migrate.php`
3. Delete `migrate.php` after success

### 5.2 Create Admin Account
1. Visit: `https://yourdomain.com`
2. You'll be redirected to `/auth/setup`
3. Enter your email address
4. Click "Create Admin Account"
5. Check your email for the magic link
6. Click the link to login

---

## Step 6: Add Your First Site

### 6.1 Create a Site
1. After login, go to **Sites** → **Add Site**
2. Enter:
   - Site Name: "My Website"
   - Domain: `mywebsite.com`
3. Click **Create Site**
4. Copy the generated site key

### 6.2 Install Tracking Code
Add this before `</body>` on your website:

```html
<script>
!function(){
  var siteKey = "YOUR-SITE-KEY-HERE"; // Replace with your site key
  var ep = "https://yourdomain.com/i.php"; // Your horizn_ domain

  function cid(){
    var m = document.cookie.match(/(?:^|;\s*)h_uid=([^;]+)/);
    if(m) return m[1];
    var v = 'h-'+Math.random().toString(16).slice(2)+'-'+Date.now();
    document.cookie = "h_uid="+v+"; Path=/; SameSite=Lax; Max-Age=63072000";
    return v;
  }
  var uid = cid();

  function send(ev){
    var payload = {
      k: siteKey,
      u: uid,
      t: ev.type,
      ts: Date.now(),
      url: location.href,
      p: location.pathname,
      r: document.referrer || null,
      w: screen.width, 
      h: screen.height
    };
    if(navigator.sendBeacon){
      navigator.sendBeacon(ep, JSON.stringify(payload));
    } else {
      fetch(ep, {method:"POST", body:JSON.stringify(payload)}).catch(function(){});
    }
  }

  send({type:"pageview"});

  // Track route changes for SPAs
  var push = history.pushState;
  history.pushState = function(){ 
    push.apply(this, arguments); 
    send({type:"pageview"}); 
  };
}();
</script>
```

---

## Step 7: Test Your Setup

### 7.1 Verify Tracking
1. Visit your website with tracking installed
2. Go to your horizn_ dashboard
3. You should see:
   - Live visitor count increase
   - Your pageview in real-time
   - Your referrer source

### 7.2 Test Ad-blocker Resistance
1. Enable uBlock Origin or AdBlock Plus
2. Visit your tracked site
3. Check dashboard - you should still be tracked!

### 7.3 Check Disguised Endpoints
These should all work:
- `https://yourdomain.com/i.php`
- `https://yourdomain.com/data.js`
- `https://yourdomain.com/h.js`
- `https://yourdomain.com/assets/data.css` (disguised)
- `https://yourdomain.com/assets/pixel.png` (disguised)

---

## Step 8: WordPress Plugin (Optional)

If you want to track WordPress sites:

### 8.1 Prepare Plugin
1. Download `/horizn-wp-plugin/` folder
2. Zip the folder as `horizn-analytics.zip`

### 8.2 Install on WordPress
1. Go to **Plugins → Add New → Upload Plugin**
2. Upload `horizn-analytics.zip`
3. Activate the plugin
4. Go to **Settings → horizn_ Analytics**
5. Enter your analytics URL: `https://yourdomain.com`
6. Generate or enter your site key
7. Save settings

---

## Troubleshooting

### Can't Login / Magic Link Not Working
- Check email spam folder
- Verify MAIL settings in .env
- Make sure APP_URL is correct
- Check PHP mail() is enabled on Hostinger

### No Data Showing
- Verify tracking code is installed
- Check browser console for errors
- Make sure site key matches
- Check database is receiving events:
  ```sql
  SELECT COUNT(*) FROM events;
  ```

### 500 Error
- Check `.env` file exists and is readable
- Verify database credentials
- Check PHP error log in Hostinger
- Make sure PHP version is 7.4+

### Ad-blocker Still Blocking
- Make sure `.htaccess` is in public_html
- Try renaming endpoints in `.htaccess`
- Use the pixel fallback method

---

## Performance Optimization

### Enable Caching
Add to `.env`:
```env
CACHE_ENABLED=true
CACHE_TTL=300
```

### Database Optimization
Run monthly in phpMyAdmin:
```sql
OPTIMIZE TABLE events;
OPTIMIZE TABLE sessions;
OPTIMIZE TABLE daily_agg;
```

### CDN for Assets (Optional)
1. Set up Cloudflare for your domain
2. Exclude `/i.php` and `/data.js` from caching
3. Cache `/assets/` folder aggressively

---

## Security Checklist

✅ Change default PEPPER in .env
✅ Use strong database password
✅ Set APP_DEBUG=false in production
✅ Keep WordPress plugin updated
✅ Regular backups of database
✅ Monitor for suspicious activity
✅ Use HTTPS everywhere

---

## Next Steps

1. **Monitor Performance**: Check dashboard load times
2. **Set Up Backups**: Configure automated database backups
3. **Custom Dashboards**: Build dashboards for different use cases
4. **Funnel Tracking**: Set up conversion funnels
5. **Journey Analysis**: Analyze user paths

---

## Support

- **GitHub**: https://github.com/tonyshawjr/horizn_
- **Issues**: Report bugs on GitHub
- **Updates**: Watch repo for new features

---

## Quick Commands Reference

### Check if tracking works:
```bash
curl -X POST https://yourdomain.com/i.php \
  -H "Content-Type: application/json" \
  -d '{"k":"your-site-key","t":"test","u":"test-user"}'
```

### Database backup:
```bash
mysqldump -u u123456789_horizn -p u123456789_horizn > backup.sql
```

### Clear old events (30+ days):
```sql
DELETE FROM events WHERE ts < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

🎉 **Congratulations! Your horizn_ analytics is live!**