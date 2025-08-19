# Magic Link Authentication System

This document explains the complete magic link authentication system implemented for horizn_ Analytics.

## Overview

The system provides secure, passwordless authentication using time-limited, single-use magic links sent via email. This eliminates password-related security vulnerabilities while providing a smooth user experience.

## Features

- **Passwordless Authentication**: No passwords to remember, manage, or compromise
- **Time-Limited Links**: All magic links expire after 15 minutes
- **Single-Use Tokens**: Each link can only be used once for maximum security
- **Rate Limiting**: Protection against abuse with request limiting
- **Database-Based Storage**: Secure token storage with proper cleanup
- **Beautiful Email Templates**: Crypto-themed, mobile-responsive email design
- **First-Time Setup**: Special handling for initial admin account creation
- **HTTPS Enforcement**: Production security features

## System Components

### 1. Database Schema
- `magic_links` table for secure token storage
- `first_login` flag on users table for setup detection
- Proper indexes and foreign key constraints

### 2. Authentication Flow
```
1. User requests login → Email entered
2. System generates secure token → Stored in database
3. Email sent with magic link → Time-limited URL
4. User clicks link → Token verification
5. Successful login → Token marked as used and cleaned up
```

### 3. Email System
- Multiple delivery methods: PHP mail(), SMTP, Sendmail
- Beautiful HTML templates with fallback text versions
- Crypto/SaaS aesthetic matching horizn_ branding
- Responsive design for all devices

## File Structure

```
/app/
├── controllers/
│   └── AuthController.php          # Updated with magic link methods
├── lib/
│   ├── Auth.php                   # Updated authentication logic
│   ├── Mail.php                   # Email sending system
│   └── email-templates/
│       └── magic-link.html        # Beautiful email template
└── views/
    └── auth/
        ├── setup.php              # Initial admin setup
        ├── magic-link-sent.php    # Confirmation page
        └── verify-magic-link.php  # Processing/error page

/database/
├── migrations.sql                 # Updated with magic links migration
└── migrate.php                   # Migration script

/public/
└── index.php                     # Updated routing
```

## Setup Instructions

### 1. Run Database Migration
```bash
php database/migrate.php
```

### 2. Configure Email Settings
Set these environment variables:

```bash
# Email method (mail, smtp, sendmail)
MAIL_METHOD=mail

# For SMTP (if using SMTP method)
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

# From address and name
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="horizn_ Analytics"

# Application URL (important for magic link generation)
APP_URL=https://yourdomain.com
```

### 3. First-Time Setup
1. Navigate to your application
2. You'll be redirected to `/auth/setup`
3. Fill in admin account details
4. Check email for setup magic link
5. Click link to complete setup

## Security Features

### 1. Token Security
- **128-character tokens** using cryptographically secure random bytes
- **Database storage** instead of session-based (more secure)
- **Automatic cleanup** of expired and used tokens
- **IP address logging** for audit trails

### 2. Rate Limiting
- **3 requests per 5 minutes** for magic link requests
- **Session-based tracking** with automatic reset
- **IP-based protection** against distributed attacks

### 3. HTTPS Enforcement
- **Secure cookie settings** for production
- **HTTPS-only magic links** in production environment
- **Proper headers** for security

### 4. User Experience
- **Real-time feedback** with processing states
- **Email client integration** (opens default mail app)
- **Webmail detection** (Gmail, Outlook, Yahoo, iCloud)
- **Resend functionality** with cooldown protection

## API Endpoints

### Authentication Routes
- `GET /auth/setup` - Initial admin setup page
- `POST /auth/setup` - Create admin account and send magic link
- `GET /auth/login` - Login page
- `POST /auth/login` - Handle login form (including magic link requests)
- `GET /auth/verify?token=XXX` - Magic link processing page
- `GET /auth/magic?token=XXX` - Magic link verification endpoint
- `GET /auth/magic-link-sent` - Confirmation page after sending link
- `GET /auth/logout` - Logout endpoint

### Magic Link Flow
```
POST /auth/login (login_type=magic)
    ↓
Email sent with link to /auth/verify?token=XXX
    ↓
Processing page shows, redirects to /auth/magic?token=XXX
    ↓
Token verified, user logged in, redirect to /dashboard
```

## Email Template Customization

The email template is located at `/app/lib/email-templates/magic-link.html` and features:

- **Dark theme** matching horizn_ aesthetic
- **Gradient backgrounds** and modern styling
- **Security notices** and usage instructions
- **Mobile responsive** design
- **Fallback plain text** version

### Template Variables
- `{{user_name}}` - User's first name
- `{{magic_link}}` - The secure login URL
- `{{expires_minutes}}` - Expiration time (15)
- `{{app_name}}` - Application name
- `{{app_url}}` - Application URL
- `{{email_title}}` - Dynamic title
- `{{email_message}}` - Dynamic message
- `{{button_text}}` - Dynamic button text

## Troubleshooting

### Common Issues

1. **Magic links not working**
   - Check APP_URL is set correctly
   - Verify database migration was applied
   - Check email delivery configuration

2. **Emails not sending**
   - Verify MAIL_* environment variables
   - Check server mail configuration
   - Test with `php -m | grep mail` for mail support

3. **Links expiring too quickly**
   - Links expire after 15 minutes by design
   - Users should check email promptly
   - Resend functionality is available

4. **Rate limiting issues**
   - Users are limited to 3 requests per 5 minutes
   - Wait for cooldown period
   - Check for session issues

### Debug Mode

Enable debug mode to see detailed error messages:
```bash
APP_DEBUG=true
```

### Log Files

Check application logs for detailed error information:
```bash
tail -f /path/to/logs/horizn.log
```

## Production Considerations

### Security Checklist
- [ ] HTTPS enforced with valid SSL certificate
- [ ] APP_URL set to production domain
- [ ] Email delivery properly configured
- [ ] Database properly secured
- [ ] Error reporting disabled in production
- [ ] Regular cleanup of expired tokens (automatic)

### Performance
- Database indexes are automatically created for optimal performance
- Expired tokens are cleaned up automatically
- Email sending is non-blocking

### Monitoring
- Monitor failed login attempts in logs
- Track magic link usage patterns
- Set up alerts for authentication failures

## Support

This magic link authentication system provides enterprise-grade security with excellent user experience. The passwordless approach eliminates many security vulnerabilities while maintaining ease of use.

For issues or enhancements, check the application logs and verify configuration settings first.