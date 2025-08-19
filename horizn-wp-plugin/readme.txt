=== horizn_ Analytics ===
Contributors: tonyshawjr
Tags: analytics, tracking, privacy, first-party, ad-blocker-resistant
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

First-party, ad-blocker resistant analytics platform with crypto/saas aesthetic. Track users without external dependencies.

== Description ==

**horizn_ Analytics** is a modern, privacy-focused analytics solution designed for WordPress websites. Unlike traditional analytics tools, horizn_ runs entirely on your own infrastructure, ensuring complete data ownership and ad-blocker resistance.

### 🚀 Key Features

* **First-Party Tracking** - All data stays on your server
* **Ad-Blocker Resistant** - Uses disguised endpoints and multiple fallbacks  
* **Privacy Compliant** - No external data sharing, GDPR friendly
* **Lightning Fast** - Minimal impact on page load times
* **WordPress Native** - Deep integration with WordPress ecosystem
* **WooCommerce Ready** - Automatic e-commerce event tracking
* **Real-Time Dashboard** - Live visitor tracking in WordPress admin
* **Crypto/SaaS Aesthetic** - Modern, professional interface design

### 🎯 What Makes horizn_ Different

Traditional analytics tools like Google Analytics are:
- ❌ Blocked by ad blockers (up to 40% of users)
- ❌ Slow to load (external scripts)  
- ❌ Share your data with third parties
- ❌ Complex to set up and understand

horizn_ Analytics is:
- ✅ **Unblockable** - Runs from your own domain
- ✅ **Lightning Fast** - No external dependencies
- ✅ **100% Private** - Your data never leaves your server
- ✅ **Simple** - Works out of the box with WordPress

### 📊 Tracking Features

**Automatic Tracking:**
* Page views and unique visitors
* User sessions and return visitors  
* Scroll depth and engagement time
* Click tracking on buttons and links
* Form submissions and downloads
* User registration and login events

**E-commerce Tracking (WooCommerce):**
* Purchase completions with order details
* Add to cart events
* Product page views
* Checkout process tracking
* Revenue and conversion analytics

**WordPress Integration:**
* Track custom post types
* Monitor plugin/theme performance impact
* User role-based tracking controls
* Multisite network support
* Integration with popular form plugins

### 🔒 Privacy & Security

* **No External Requests** - Everything runs on your server
* **Cookie-Free Option** - Use browser fingerprinting instead
* **Anonymization** - IP addresses can be anonymized  
* **Opt-Out Friendly** - Respects user privacy choices
* **GDPR Compliant** - Built with privacy regulations in mind
* **Data Retention Controls** - Automatically clean old data

### 🎨 Admin Experience

* **Beautiful Dashboard Widget** - See live stats in WordPress admin
* **Crypto-Inspired Design** - Modern gradient interface
* **One-Click Setup** - Auto-generates tracking codes
* **Connection Testing** - Verify tracking is working correctly
* **Advanced Configuration** - Customize endpoints and settings

== Installation ==

### Automatic Installation

1. Go to your WordPress admin → Plugins → Add New
2. Search for "horizn_ Analytics"
3. Click "Install Now" and then "Activate"
4. Go to Settings → horizn_ Analytics to configure

### Manual Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/horizn-analytics/`
3. Activate through the 'Plugins' menu in WordPress
4. Go to Settings → horizn_ Analytics to configure

### Quick Setup

1. **Generate Site Key** - Click "Generate New Site Key" in settings
2. **Set API Endpoint** - Usually your website URL (auto-detected)
3. **Enable Tracking** - Check "Enable analytics tracking"
4. **Test Connection** - Click "Test Connection" to verify setup
5. **Save Settings** - Your site is now being tracked!

== Frequently Asked Questions ==

= Why is my tracking blocked by ad blockers? =

Unlike traditional analytics, horizn_ is specifically designed to be ad-blocker resistant. It uses:
- Multiple disguised endpoints (/wp-content/themes/assets/data.js, etc.)
- First-party requests (same domain)
- Multiple fallback methods (Beacon API, Fetch, XHR)

If you're still seeing blocks, check that your server can receive POST requests to the configured endpoints.

= How is this different from Google Analytics? =

**Privacy:** horizn_ keeps all data on your server vs. Google's servers
**Performance:** No external scripts to load vs. ~50KB+ external script  
**Accuracy:** Ad-blockers can't block first-party requests vs. 20-40% blocked
**Control:** You own your data 100% vs. shared with Google
**Compliance:** GDPR-friendly by default vs. requires consent management

= Does this work with caching plugins? =

Yes! horizn_ is designed to work with all major caching plugins:
- WP Rocket
- W3 Total Cache  
- WP Super Cache
- LiteSpeed Cache
- Cloudflare

The tracking script is injected dynamically and doesn't interfere with page caching.

= Can I use this with WooCommerce? =

Absolutely! horizn_ includes built-in WooCommerce integration:
- Automatic purchase tracking
- Add to cart events  
- Product page analytics
- Revenue reporting
- Customer journey tracking

Enable "E-commerce Tracking" in settings and it will automatically start tracking WooCommerce events.

= Is this GDPR compliant? =

Yes, horizn_ is designed to be privacy-friendly:
- No external data sharing
- Optional cookie-free tracking
- User opt-out controls
- Data anonymization options
- Automatic data cleanup

However, you should still review your privacy policy and consult legal counsel for complete compliance.

= How much does this impact page speed? =

Minimal impact:
- ~2KB compressed tracking script
- No external requests 
- Asynchronous loading
- Batched event sending
- Local processing only

Most sites see no measurable performance impact.

= Can I customize the tracking? =

Yes! horizn_ is highly customizable:
- Custom events via `horizn_track_event()`
- User identification with `horizn_identify_user()`
- Custom page data with `horizn_track_page()`
- Configurable endpoints and settings
- Developer hooks and filters

== Screenshots ==

1. **Modern Admin Interface** - Crypto-inspired design with live status indicators
2. **Dashboard Widget** - See analytics directly in WordPress admin  
3. **Settings Page** - Simple configuration with test connection features
4. **Live Tracking** - Real-time visitor and event tracking
5. **WooCommerce Integration** - Automatic e-commerce event tracking
6. **Privacy Controls** - GDPR-friendly options and user controls

== Changelog ==

= 1.0.0 =
**Release Date: August 19, 2025**

**Features:**
* Initial plugin release
* First-party analytics tracking
* Ad-blocker resistant implementation
* WordPress admin integration
* WooCommerce e-commerce tracking
* Privacy-focused design
* Modern crypto/SaaS interface
* Dashboard widget with live stats
* Automatic event tracking (clicks, scrolls, forms)
* Multi-endpoint fallback system
* Session and user identification
* Custom event tracking API
* GDPR compliance features
* Multisite network support
* Caching plugin compatibility

**Technical:**
* PHP 7.4+ compatibility
* WordPress 5.0+ support
* Async script loading
* Multiple tracking endpoints
* Beacon API support with fallbacks
* Cookie-free tracking option
* IP anonymization
* Data batching and compression

== Upgrade Notice ==

= 1.0.0 =
Initial release of horizn_ Analytics. A modern, privacy-focused alternative to Google Analytics that's unblockable by ad blockers.

== Developer Information ==

### Hooks & Filters

**Actions:**
* `horizn_before_tracking` - Fired before tracking script injection
* `horizn_after_tracking` - Fired after tracking script injection  
* `horizn_event_tracked` - Fired when an event is tracked

**Filters:**
* `horizn_tracking_enabled` - Control if tracking should be enabled
* `horizn_tracking_script` - Modify the tracking script
* `horizn_event_data` - Modify event data before sending

### Functions

```php
// Track custom events
horizn_track_event([
    'name' => 'button_click',
    'category' => 'interaction', 
    'value' => 1
]);

// Identify users
horizn_identify_user(123, [
    'email' => 'user@example.com',
    'plan' => 'premium'
]);

// Track page with custom data  
horizn_track_page([
    'post_type' => 'product',
    'author' => 'John Doe'
]);
```

### Requirements

* PHP 7.4 or higher
* WordPress 5.0 or higher  
* MySQL 5.6 or higher
* SSL certificate recommended
* Ability to receive POST requests

### Support

* **Documentation:** [GitHub Repository](https://github.com/tonyshawjr/horizn_)
* **Issues:** Report bugs on GitHub
* **Feature Requests:** Submit via GitHub Issues

Built with ❤️ by [Tony Shaw Jr](https://tonyshaw.dev)