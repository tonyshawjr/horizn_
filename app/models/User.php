<?php
/**
 * User Model
 * 
 * Manages platform users and authentication data.
 */

class User
{
    /**
     * Get user by ID
     */
    public static function getById(int $user_id): ?array
    {
        return Database::selectOne(
            "SELECT id, username, email, first_name, last_name, created_at, updated_at, last_login, is_active
             FROM users 
             WHERE id = ? AND is_active = 1",
            [$user_id]
        );
    }
    
    /**
     * Get user by email
     */
    public static function getByEmail(string $email): ?array
    {
        return Database::selectOne(
            "SELECT id, username, email, password_hash, first_name, last_name, created_at, last_login, is_active
             FROM users 
             WHERE email = ?",
            [$email]
        );
    }
    
    /**
     * Get user by username
     */
    public static function getByUsername(string $username): ?array
    {
        return Database::selectOne(
            "SELECT id, username, email, password_hash, first_name, last_name, created_at, last_login, is_active
             FROM users 
             WHERE username = ?",
            [$username]
        );
    }
    
    /**
     * Create new user
     */
    public static function create(array $data): array
    {
        try {
            // Validate required fields
            $validation = self::validateUserData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Check if user already exists
            $existing_user = Database::selectOne(
                "SELECT id FROM users WHERE email = ? OR username = ?",
                [$data['email'], $data['username']]
            );
            
            if ($existing_user) {
                return [
                    'success' => false,
                    'error' => 'User with this email or username already exists.'
                ];
            }
            
            // Hash password
            $config = require CONFIG_PATH . '/app.php';
            $password_hash = password_hash($data['password'], $config['security']['password_hash_algo']);
            
            // Insert user
            $user_id = Database::insert(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())",
                [
                    $data['username'],
                    $data['email'],
                    $password_hash,
                    $data['first_name'] ?? '',
                    $data['last_name'] ?? ''
                ]
            );
            
            if ($user_id) {
                return [
                    'success' => true,
                    'user_id' => $user_id,
                    'message' => 'User created successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to create user.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'User creation failed.'
            ];
        }
    }
    
    /**
     * Update user profile
     */
    public static function update(int $user_id, array $data): array
    {
        try {
            // Verify user exists
            $user = self::getById($user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            // Build update query
            $update_fields = [];
            $params = [];
            
            if (!empty($data['first_name'])) {
                $update_fields[] = 'first_name = ?';
                $params[] = $data['first_name'];
            }
            
            if (!empty($data['last_name'])) {
                $update_fields[] = 'last_name = ?';
                $params[] = $data['last_name'];
            }
            
            if (!empty($data['email'])) {
                // Check if email is taken by another user
                $existing_email = Database::selectOne(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$data['email'], $user_id]
                );
                
                if ($existing_email) {
                    return [
                        'success' => false,
                        'error' => 'Email address is already taken.'
                    ];
                }
                
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return [
                        'success' => false,
                        'error' => 'Invalid email address format.'
                    ];
                }
                
                $update_fields[] = 'email = ?';
                $params[] = $data['email'];
            }
            
            if (!empty($data['username'])) {
                // Check if username is taken by another user
                $existing_username = Database::selectOne(
                    "SELECT id FROM users WHERE username = ? AND id != ?",
                    [$data['username'], $user_id]
                );
                
                if ($existing_username) {
                    return [
                        'success' => false,
                        'error' => 'Username is already taken.'
                    ];
                }
                
                if (!self::isValidUsername($data['username'])) {
                    return [
                        'success' => false,
                        'error' => 'Invalid username format.'
                    ];
                }
                
                $update_fields[] = 'username = ?';
                $params[] = $data['username'];
            }
            
            if (empty($update_fields)) {
                return [
                    'success' => false,
                    'error' => 'No fields to update.'
                ];
            }
            
            $update_fields[] = 'updated_at = NOW()';
            $params[] = $user_id;
            
            $affected_rows = Database::update(
                "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?",
                $params
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No changes were made.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Profile update failed.'
            ];
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword(int $user_id, string $current_password, string $new_password): array
    {
        try {
            // Get user with password hash
            $user = Database::selectOne(
                "SELECT id, password_hash FROM users WHERE id = ? AND is_active = 1",
                [$user_id]
            );
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'error' => 'Current password is incorrect.'
                ];
            }
            
            // Validate new password
            $config = require CONFIG_PATH . '/app.php';
            $min_length = $config['security']['password_min_length'];
            
            if (strlen($new_password) < $min_length) {
                return [
                    'success' => false,
                    'error' => "New password must be at least {$min_length} characters long."
                ];
            }
            
            // Hash new password
            $new_password_hash = password_hash($new_password, $config['security']['password_hash_algo']);
            
            // Update password
            $affected_rows = Database::update(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$new_password_hash, $user_id]
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to change password.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Password change failed.'
            ];
        }
    }
    
    /**
     * Reset user password (admin function)
     */
    public static function resetPassword(int $user_id, string $new_password): array
    {
        try {
            $user = self::getById($user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            // Validate new password
            $config = require CONFIG_PATH . '/app.php';
            $min_length = $config['security']['password_min_length'];
            
            if (strlen($new_password) < $min_length) {
                return [
                    'success' => false,
                    'error' => "Password must be at least {$min_length} characters long."
                ];
            }
            
            // Hash password
            $password_hash = password_hash($new_password, $config['security']['password_hash_algo']);
            
            // Update password
            $affected_rows = Database::update(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$password_hash, $user_id]
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Password reset successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to reset password.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Password reset failed.'
            ];
        }
    }
    
    /**
     * Deactivate user account
     */
    public static function deactivate(int $user_id): array
    {
        try {
            $affected_rows = Database::update(
                "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?",
                [$user_id]
            );
            
            if ($affected_rows > 0) {
                // Also deactivate user's sites
                Database::update(
                    "UPDATE sites SET is_active = 0, updated_at = NOW() WHERE user_id = ?",
                    [$user_id]
                );
                
                return [
                    'success' => true,
                    'message' => 'User account deactivated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to deactivate user account.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("User deactivation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'User deactivation failed.'
            ];
        }
    }
    
    /**
     * Reactivate user account
     */
    public static function reactivate(int $user_id): array
    {
        try {
            $affected_rows = Database::update(
                "UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?",
                [$user_id]
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'User account reactivated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to reactivate user account.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("User reactivation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'User reactivation failed.'
            ];
        }
    }
    
    /**
     * Get user's dashboard statistics
     */
    public static function getDashboardStats(int $user_id): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(s.id) as total_sites,
                COUNT(CASE WHEN s.is_active = 1 THEN 1 END) as active_sites,
                COALESCE(SUM(site_stats.total_pageviews), 0) as total_pageviews,
                COALESCE(SUM(site_stats.unique_visitors), 0) as total_unique_visitors
             FROM users u
             LEFT JOIN sites s ON u.id = s.user_id
             LEFT JOIN (
                SELECT 
                    site_id,
                    COUNT(p.id) as total_pageviews,
                    COUNT(DISTINCT ses.user_hash) as unique_visitors
                FROM sessions ses
                LEFT JOIN pageviews p ON ses.id = p.session_id
                WHERE DATE(ses.first_visit) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY site_id
             ) site_stats ON s.id = site_stats.site_id
             WHERE u.id = ?
             GROUP BY u.id",
            [$user_id],
            true, // use cache
            300   // 5 minutes
        );
        
        return $stats ?: [
            'total_sites' => 0,
            'active_sites' => 0,
            'total_pageviews' => 0,
            'total_unique_visitors' => 0
        ];
    }
    
    /**
     * Get user's recent activity
     */
    public static function getRecentActivity(int $user_id, int $limit = 10): array
    {
        return Database::select(
            "SELECT 
                'site_created' as activity_type,
                s.name as description,
                s.created_at as timestamp
             FROM sites s
             WHERE s.user_id = ? 
             AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             
             UNION ALL
             
             SELECT 
                'site_updated' as activity_type,
                CONCAT(s.name, ' settings updated') as description,
                s.updated_at as timestamp
             FROM sites s
             WHERE s.user_id = ? 
             AND s.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             AND s.updated_at != s.created_at
             
             ORDER BY timestamp DESC
             LIMIT ?",
            [$user_id, $user_id, $limit]
        );
    }
    
    /**
     * Get all users (admin function)
     */
    public static function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        return Database::select(
            "SELECT 
                u.id, u.username, u.email, u.first_name, u.last_name,
                u.created_at, u.last_login, u.is_active,
                COUNT(s.id) as total_sites,
                COUNT(CASE WHEN s.is_active = 1 THEN 1 END) as active_sites
             FROM users u
             LEFT JOIN sites s ON u.id = s.user_id
             GROUP BY u.id, u.username, u.email, u.first_name, u.last_name, u.created_at, u.last_login, u.is_active
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    /**
     * Search users
     */
    public static function searchUsers(string $query, int $limit = 20): array
    {
        $search_term = "%{$query}%";
        
        return Database::select(
            "SELECT id, username, email, first_name, last_name, created_at, is_active
             FROM users
             WHERE (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             ORDER BY username ASC
             LIMIT ?",
            [$search_term, $search_term, $search_term, $search_term, $limit]
        );
    }
    
    /**
     * Validate user data
     */
    private static function validateUserData(array $data): array
    {
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (!self::isValidUsername($data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, hyphens, and underscores (3-50 characters)';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        } elseif (strlen($data['email']) > 255) {
            $errors[] = 'Email address is too long';
        }
        
        // Password validation
        if (!empty($data['password'])) {
            $config = require CONFIG_PATH . '/app.php';
            $min_length = $config['security']['password_min_length'];
            
            if (strlen($data['password']) < $min_length) {
                $errors[] = "Password must be at least {$min_length} characters long";
            }
        }
        
        // Name validation (optional)
        if (!empty($data['first_name']) && strlen($data['first_name']) > 100) {
            $errors[] = 'First name is too long';
        }
        if (!empty($data['last_name']) && strlen($data['last_name']) > 100) {
            $errors[] = 'Last name is too long';
        }
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(', ', $errors)
        ];
    }
    
    /**
     * Validate username format
     */
    private static function isValidUsername(string $username): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username);
    }
    
    /**
     * Update last login timestamp
     */
    public static function updateLastLogin(int $user_id): void
    {
        Database::update(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user_id]
        );
    }
    
    /**
     * Get user preferences
     */
    public static function getPreferences(int $user_id): array
    {
        // For now, return default preferences
        // In the future, you might want to add a user_preferences table
        return [
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'theme' => 'dark',
            'dashboard_refresh_interval' => 30,
            'email_notifications' => true
        ];
    }
    
    /**
     * Update user preferences
     */
    public static function updatePreferences(int $user_id, array $preferences): array
    {
        // Placeholder for preference updates
        // Implement based on your preference storage strategy
        
        return [
            'success' => true,
            'message' => 'Preferences updated successfully.'
        ];
    }
    
    /**
     * Export user data (GDPR compliance)
     */
    public static function exportUserData(int $user_id): array
    {
        try {
            $user = self::getById($user_id);
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            // Get user's sites
            $sites = Site::getUserSites($user_id);
            
            // Compile user data
            $export_data = [
                'user_profile' => $user,
                'sites' => $sites,
                'preferences' => self::getPreferences($user_id),
                'export_timestamp' => date('Y-m-d H:i:s')
            ];
            
            return [
                'success' => true,
                'data' => json_encode($export_data, JSON_PRETTY_PRINT),
                'filename' => "user_{$user_id}_data_export_" . date('Y-m-d') . ".json"
            ];
            
        } catch (Exception $e) {
            error_log("User data export error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Data export failed.'
            ];
        }
    }
    
    /**
     * Delete user account and all associated data (GDPR compliance)
     */
    public static function deleteAccount(int $user_id): array
    {
        try {
            Database::beginTransaction();
            
            // Delete user's analytics data
            $user_sites = Site::getUserSites($user_id);
            foreach ($user_sites as $site) {
                // Delete sessions and related data
                Database::delete("DELETE FROM events WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM pageviews WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM sessions WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM realtime_visitors WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM daily_stats WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM page_stats WHERE site_id = ?", [$site['id']]);
                Database::delete("DELETE FROM referrer_stats WHERE site_id = ?", [$site['id']]);
            }
            
            // Delete sites
            Database::delete("DELETE FROM sites WHERE user_id = ?", [$user_id]);
            
            // Delete user account
            Database::delete("DELETE FROM users WHERE id = ?", [$user_id]);
            
            Database::commit();
            
            return [
                'success' => true,
                'message' => 'User account and all associated data deleted successfully.'
            ];
            
        } catch (Exception $e) {
            Database::rollback();
            error_log("User account deletion error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Account deletion failed.'
            ];
        }
    }
}
?>