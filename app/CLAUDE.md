# Application Folder - Core Backend Logic

## Purpose
Contains all server-side application logic for horizn_ analytics platform using MVC architecture.

## Rules
- **MVC Pattern**: Strict separation of Models, Views, Controllers
- **PHP 8.0+**: Use modern PHP features (typed properties, match expressions)
- **Security First**: All inputs validated, outputs escaped
- **Performance**: Optimize database queries, use prepared statements
- **Error Handling**: Comprehensive error handling and logging

## Folder Structure
```
/config/          # Configuration files
/controllers/     # MVC Controllers
/models/         # Data models and database interaction
/views/          # HTML templates and UI components  
/lib/            # Core library and utility functions
```

## Coding Standards
- **PSR-12** coding standard compliance
- **Type hints** on all function parameters and returns
- **DocBlocks** for all classes and methods
- **Namespaces** for proper code organization
- **Exception handling** for all database operations

## Security Requirements
- **Input validation** on all user data
- **SQL injection** prevention with prepared statements
- **XSS prevention** with output escaping
- **CSRF protection** on all forms
- **Authentication** checks on protected routes

## Database Interaction
- Use prepared statements exclusively
- Implement connection pooling for performance
- Transaction handling for data consistency
- Proper error handling for database failures
- Implement query caching where appropriate

## Primary Agents
- backend-architect
- rapid-prototyper
- test-writer-fixer
- api-tester

## Performance Considerations
- Cache frequently accessed data
- Optimize database queries with proper indexing
- Use connection pooling for database access
- Implement proper error logging
- Monitor query performance