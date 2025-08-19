# Public Folder - Web Root

## Purpose
Web-accessible root directory for horizn_ analytics platform. Contains the main entry point and all public assets.

## Rules
- **index.php** - Main application entry point with routing
- **assets/** - All static assets (CSS, JS, images)
- **.htaccess** - Apache configuration for clean URLs and ad-blocker resistance
- NO sensitive files should be placed here
- All PHP files should have proper security headers
- Use clean URLs for all routes

## Ad-blocker Resistance Strategy
- Tracking endpoints disguised as asset requests
- Multiple fallback patterns for different blockers
- First-party domain requests only

## Security Requirements
- Force HTTPS in production
- Proper CSP headers set in .htaccess
- No direct database access from this folder
- All inputs must be validated before processing

## Primary Agents
- rapid-prototyper
- frontend-developer
- backend-architect
- test-writer-fixer

## File Patterns
```
index.php         # Main entry point
.htaccess        # Apache configuration
/assets/
  /css/          # Stylesheets
  /js/           # JavaScript files
  /img/          # Images and icons
```

## Performance Notes
- Assets should be minified for production
- Proper caching headers set in .htaccess
- Compression enabled for text files
- Static assets should be CDN-ready