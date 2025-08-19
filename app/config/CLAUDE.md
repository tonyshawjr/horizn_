# Configuration Folder

## Purpose
Contains all configuration files for the horizn_ analytics platform including database, application settings, and environment-specific configurations.

## Rules
- **Environment-specific**: Separate configs for development, staging, production
- **Security**: No sensitive data in version control (use .env files)
- **Constants**: Define all application constants here
- **Validation**: Validate all configuration values
- **Documentation**: Comment all configuration options

## Required Files
```
app.php           # Main application configuration
database.php      # Database connection settings
session.php       # Session configuration
cache.php         # Caching configuration (if implemented)
mail.php          # Email settings (if needed)
.env.example      # Environment variables example
```

## Configuration Patterns
```php
<?php
// Example configuration structure
return [
    'app' => [
        'name' => 'horizn_',
        'version' => '0.1.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'timezone' => 'UTC',
    ],
    'security' => [
        'session_lifetime' => 1800, // 30 minutes
        'csrf_token_name' => '_token',
        'password_hash_algo' => PASSWORD_ARGON2ID,
    ]
];
```

## Environment Variables
```env
# Database
DB_HOST=localhost
DB_NAME=horizn_analytics
DB_USER=username
DB_PASS=password

# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-secret-key-here

# Session
SESSION_LIFETIME=1800
SESSION_SECURE=true
```

## Primary Agents
- backend-architect
- devops-automator
- infrastructure-maintainer

## Security Notes
- Never commit .env files to version control
- Use strong, unique keys for encryption
- Validate all environment variables
- Set secure defaults for all settings
- Use different keys for different environments