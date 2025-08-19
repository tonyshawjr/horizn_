# Database Folder - Schema and Migrations

## Purpose
Contains database schema, migration files, and database-related scripts for the horizn_ analytics platform.

## Rules
- **Version Control**: All schema changes must be in migrations
- **Performance**: Proper indexing for all queries
- **Data Integrity**: Foreign key constraints and validation
- **Privacy**: IP hashing and data anonymization
- **Scalability**: Design for high-volume analytics data

## File Structure
```
schema.sql           # Complete database schema
migrations.sql       # Migration system and version control
seed.sql            # Sample data for development (if created)
backup.sql          # Database backup scripts (if created)
```

## Schema Design Principles

### High Performance Tables
- **pageviews**: Optimized for high-volume inserts
- **events**: Custom event tracking with JSON flexibility
- **sessions**: User session aggregation
- **daily_stats**: Pre-aggregated data for dashboard performance

### Privacy Compliance
- **IP Hashing**: All IP addresses stored as SHA-256 hashes
- **Session Anonymization**: Secure session ID generation
- **Data Retention**: Configurable automatic cleanup
- **No PII**: No personal identifiable information stored

### Indexing Strategy
```sql
-- High-frequency query indexes
INDEX idx_site_timestamp (site_id, timestamp)
INDEX idx_session (session_id)
INDEX idx_user_hash (user_hash)

-- Performance optimization indexes  
INDEX idx_site_timestamp_path (site_id, timestamp, page_path)
INDEX idx_site_event_timestamp (site_id, event_name, timestamp)
```

## Migration System
- **Version Control**: Track all database changes
- **Rollback Support**: Ability to rollback migrations
- **Environment Safety**: Prevent accidental production changes
- **Validation**: Check migration status before execution

## Key Tables Overview

### Core Analytics Tables
- **sites**: Website/domain management
- **sessions**: User session tracking
- **pageviews**: Individual page visits
- **events**: Custom event tracking
- **realtime_visitors**: Live visitor tracking

### Aggregation Tables
- **daily_stats**: Daily aggregated statistics
- **page_stats**: Page-level performance metrics
- **referrer_stats**: Referrer traffic analysis

### System Tables
- **users**: Platform user accounts
- **settings**: System configuration
- **migrations**: Database version tracking

## Performance Considerations
- **Partitioning**: Large tables partitioned by date
- **Archival**: Automatic data archiving for old records
- **Indexing**: Strategic indexes for common query patterns
- **Connection Pooling**: Efficient database connection management

## Backup Strategy
- **Regular Backups**: Automated daily backups
- **Point-in-time Recovery**: Transaction log backups
- **Testing**: Regular backup restoration testing
- **Offsite Storage**: Secure offsite backup storage

## Primary Agents
- backend-architect
- devops-automator
- analytics-reporter
- infrastructure-maintainer

## Monitoring and Maintenance
- **Query Performance**: Monitor slow query log
- **Index Usage**: Analyze index effectiveness
- **Storage Growth**: Monitor table size growth
- **Connection Monitoring**: Track database connections
- **Error Logging**: Log all database errors

## Security Measures
- **Access Control**: Restricted database user permissions
- **Connection Security**: SSL/TLS encrypted connections
- **Audit Logging**: Log all administrative actions
- **Regular Updates**: Keep database software updated
- **Vulnerability Scanning**: Regular security assessments