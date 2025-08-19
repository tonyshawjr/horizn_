# Changelog

All notable changes to horizn_ analytics platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-08-19

### Added
- Complete first-party analytics platform with ad-blocker resistance
- Multi-tenant agency dashboard with live visitor tracking (247 visitors shown)
- Magic link authentication system (15-minute expiration, single-use tokens)
- Journey tracking with identity merging across devices
- Funnel analysis with multi-step conversion tracking
- Custom dashboard builder with drag-drop GridStack.js interface
- WordPress plugin wrapper with auto-identify on login
- Ultra-lightweight tracking script (h.js, <2KB minified)
- Multiple disguised tracking endpoints for ad-blocker bypass
- Real-time analytics with 5-minute active window
- Session reconstruction and user path visualization
- Comprehensive REST API for all analytics data
- Privacy-focused tracking with SHA256 hashing of all PII
- Event tracking system with custom events and metadata
- Geographic and device analytics (browser, OS, screen size)

### UI/UX Implementation
- Crypto/SaaS aesthetic with dark mode first design
- Preline UI components with heavy customization
- ApexCharts integration with custom dark theme
- JetBrains Mono font for all data/numbers
- Minimal border radius (2-4px) for sharp edges
- Subtle glow effects instead of heavy shadows
- Responsive design for all screen sizes
- Live data updates every 30 seconds

### Backend Implementation
- Clean PHP without framework for shared hosting compatibility
- PDO database layer with connection pooling
- Optimized MySQL schema with proper indexing
- Magic link email system with SMTP/mail() support
- Rate limiting on authentication endpoints
- Automatic session cleanup and token expiration
- Database migrations system
- Comprehensive seed data for testing

### Security & Privacy
- All IP addresses and user agents hashed
- Configurable pepper for additional security
- SameSite=Lax cookies
- CSRF protection on all forms
- Single-use magic link tokens
- Database-backed session management
- No third-party dependencies or CDN requirements

### WordPress Plugin Features
- Complete plugin with admin interface
- Dashboard widget showing live analytics
- WooCommerce integration ready
- Multisite compatible
- Caching plugin friendly
- Auto-identify on wp_login hook
- Custom event tracking helpers

### Project Context
- GitHub repository created: https://github.com/tonyshawjr/horizn_
- All 12 planned tasks completed
- Ready for production deployment
- Comprehensive documentation in place