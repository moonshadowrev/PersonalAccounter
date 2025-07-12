<?php

require_once __DIR__ . '/Model.php';

class ApiKey extends Model {
    
    protected $table = 'api_keys';
    
    /**
     * Generate a new API key
     */
    public function generateApiKey($name, $userId, $permissions = null, $rateLimitPerMinute = 60, $expiresAt = null) {
        // Generate a secure random API key
        $rawKey = 'ak_' . bin2hex(random_bytes(32)); // 64 character key with prefix
        $keyPrefix = substr($rawKey, 0, 8); // First 8 characters for identification
        $hashedKey = hash('sha256', $rawKey);
        
        $data = [
            'name' => $name,
            'api_key' => $hashedKey,
            'api_key_prefix' => $keyPrefix,
            'user_id' => $userId,
            'permissions' => $permissions ? json_encode($permissions) : null,
            'rate_limit_per_minute' => $rateLimitPerMinute,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'failed_attempts' => 0,
            'blocked_until' => null
        ];
        
        $result = $this->db->insert($this->table, $data);
        
        if ($result->rowCount() > 0) {
            $keyId = $this->db->id();
            return [
                'id' => $keyId,
                'raw_key' => $rawKey, // Return raw key only once
                'prefix' => $keyPrefix,
                'name' => $name
            ];
        }
        
        return false;
    }
    
    /**
     * Validate API key and return key data if valid
     */
    public function validateApiKey($rawKey) {
        if (empty($rawKey) || !str_starts_with($rawKey, 'ak_')) {
            return false;
        }
        
        $hashedKey = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 8);
        
        $apiKey = $this->db->get($this->table, '*', [
            'api_key' => $hashedKey,
            'api_key_prefix' => $keyPrefix,
            'is_active' => true
        ]);
        
        if (!$apiKey) {
            return false;
        }
        
        // Check if key is expired
        if ($apiKey['expires_at'] && strtotime($apiKey['expires_at']) < time()) {
            return false;
        }
        
        // Check if key is currently blocked
        if ($apiKey['blocked_until'] && strtotime($apiKey['blocked_until']) > time()) {
            return false;
        }
        
        return $apiKey;
    }
    
    /**
     * Record successful API key usage
     */
    public function recordUsage($keyId) {
        return $this->db->update($this->table, [
            'last_used_at' => date('Y-m-d H:i:s'),
            'failed_attempts' => 0 // Reset failed attempts on successful use
        ], [
            'id' => $keyId
        ]);
    }
    
    /**
     * Record failed API key attempt
     */
    public function recordFailedAttempt($keyId) {
        $apiKey = $this->find($keyId);
        if (!$apiKey) {
            return false;
        }
        
        $failedAttempts = $apiKey['failed_attempts'] + 1;
        $updateData = ['failed_attempts' => $failedAttempts];
        
        // Block key if too many failed attempts (similar to login attempts)
        $maxAttempts = Config::get('api.max_failed_attempts', 5);
        $blockDuration = Config::get('api.block_duration', 300); // 5 minutes
        
        if ($failedAttempts >= $maxAttempts) {
            $updateData['blocked_until'] = date('Y-m-d H:i:s', time() + $blockDuration);
        }
        
        return $this->db->update($this->table, $updateData, [
            'id' => $keyId
        ]);
    }
    
    /**
     * Get all API keys for a user
     */
    public function getUserApiKeys($userId) {
        return $this->db->select($this->table, [
            'id',
            'name',
            'api_key_prefix',
            'permissions',
            'rate_limit_per_minute',
            'last_used_at',
            'expires_at',
            'is_active',
            'failed_attempts',
            'blocked_until',
            'created_at'
        ], [
            'user_id' => $userId,
            'ORDER' => ['created_at' => 'DESC']
        ]);
    }
    
    /**
     * Deactivate an API key
     */
    public function deactivateKey($keyId, $userId) {
        return $this->db->update($this->table, [
            'is_active' => false
        ], [
            'id' => $keyId,
            'user_id' => $userId // Ensure user can only deactivate their own keys
        ]);
    }
    
    /**
     * Permanently delete an API key
     */
    public function deleteKey($keyId, $userId) {
        // First verify the key belongs to the user
        $apiKey = $this->db->get($this->table, ['id', 'user_id'], [
            'id' => $keyId,
            'user_id' => $userId
        ]);
        
        if (!$apiKey) {
            return false;
        }
        
        // Clean up rate limit cache files for this key
        $cachePattern = __DIR__ . "/../../sessions/rate_limit_api_rate_limit_{$keyId}.json";
        @unlink($cachePattern);
        
        // Delete the API key from database
        return $this->db->delete($this->table, [
            'id' => $keyId,
            'user_id' => $userId // Ensure user can only delete their own keys
        ]);
    }
    
    /**
     * Check if API key has specific permission
     */
    public function hasPermission($apiKey, $permission) {
        if (empty($apiKey['permissions'])) {
            return true; // No restrictions means full access
        }
        
        $permissions = json_decode($apiKey['permissions'], true);
        if (!is_array($permissions)) {
            return true; // Invalid permissions JSON means full access
        }
        
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }
    
    /**
     * Get all API keys with user information (for admin access)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select($this->table, [
                '[>]users' => ['user_id' => 'id']
            ], [
                'api_keys.id',
                'api_keys.name',
                'api_keys.api_key_prefix',
                'api_keys.permissions',
                'api_keys.rate_limit_per_minute',
                'api_keys.last_used_at',
                'api_keys.expires_at',
                'api_keys.is_active',
                'api_keys.failed_attempts',
                'api_keys.blocked_until',
                'api_keys.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['api_keys.created_at' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all API keys with filters (admin access)
     */
    public function getAllWithFilters($filters = [], $page = 1, $limit = 20) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters, $page, $limit) {
            $conditions = [];
            $joins = [
                '[>]users' => ['user_id' => 'id']
            ];
            
            // Apply filters
            if (isset($filters['is_active'])) {
                $conditions['api_keys.is_active'] = $filters['is_active'];
            }
            
            if (!empty($filters['user_id'])) {
                $conditions['api_keys.user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['expires_before'])) {
                $conditions['api_keys.expires_at[<=]'] = $filters['expires_before'];
            }
            
            if (!empty($filters['expires_after'])) {
                $conditions['api_keys.expires_at[>=]'] = $filters['expires_after'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'api_keys.name[~]' => $filters['search'],
                    'api_keys.api_key_prefix[~]' => $filters['search']
                ];
            }
            
            // Pagination
            $offset = ($page - 1) * $limit;
            $conditions['LIMIT'] = [$offset, $limit];
            $conditions['ORDER'] = ['api_keys.created_at' => 'DESC'];
            
            $columns = [
                'api_keys.id',
                'api_keys.name',
                'api_keys.api_key_prefix',
                'api_keys.permissions',
                'api_keys.rate_limit_per_minute',
                'api_keys.last_used_at',
                'api_keys.expires_at',
                'api_keys.is_active',
                'api_keys.failed_attempts',
                'api_keys.blocked_until',
                'api_keys.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ];
            
            return $this->db->select($this->table, $joins, $columns, $conditions);
        }, []);
    }

    /**
     * Deactivate API key (admin access - any user)
     */
    public function adminDeactivateKey($keyId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($keyId) {
            return $this->db->update($this->table, [
                'is_active' => false
            ], [
                'id' => $keyId
            ]);
        }, false);
    }

    /**
     * Delete API key (admin access - any user)
     */
    public function adminDeleteKey($keyId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($keyId) {
            // Clean up rate limit cache files for this key
            $cachePattern = __DIR__ . "/../../sessions/rate_limit_api_rate_limit_{$keyId}.json";
            @unlink($cachePattern);
            
            // Delete the API key from database
            return $this->db->delete($this->table, [
                'id' => $keyId
            ]);
        }, false);
    }

    /**
     * Get API key statistics (admin access)
     */
    public function getApiKeyStats() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            $totalKeys = $this->db->count($this->table);
            $activeKeys = $this->db->count($this->table, ['is_active' => true]);
            $inactiveKeys = $this->db->count($this->table, ['is_active' => false]);
            $expiredKeys = $this->db->count($this->table, [
                'expires_at[<=]' => date('Y-m-d H:i:s'),
                'expires_at[!]' => null
            ]);
            
            return [
                'total_keys' => $totalKeys,
                'active_keys' => $activeKeys,
                'inactive_keys' => $inactiveKeys,
                'expired_keys' => $expiredKeys
            ];
        }, [
            'total_keys' => 0,
            'active_keys' => 0,
            'inactive_keys' => 0,
            'expired_keys' => 0
        ]);
    }

    /**
     * Check rate limit for API key
     */
    public function checkRateLimit($keyId) {
        $cacheKey = "api_rate_limit_{$keyId}";
        $rateLimitData = $this->getCachedRateLimit($cacheKey);
        
        if (!$rateLimitData) {
            // First request in this minute
            $this->setCachedRateLimit($cacheKey, 1);
            return true;
        }
        
        $apiKey = $this->find($keyId);
        if (!$apiKey) {
            return false;
        }
        
        $limit = $apiKey['rate_limit_per_minute'];
        
        if ($rateLimitData['count'] >= $limit) {
            return false; // Rate limit exceeded
        }
        
        // Increment counter
        $this->setCachedRateLimit($cacheKey, $rateLimitData['count'] + 1);
        return true;
    }
    
    /**
     * Simple file-based rate limit cache (you can replace with Redis/Memcached)
     */
    private function getCachedRateLimit($key) {
        $cacheFile = __DIR__ . "/../../sessions/rate_limit_{$key}.json";
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Check if cache is still valid (within current minute)
        if ($data && $data['expires'] > time()) {
            return $data;
        }
        
        // Clean up expired cache
        @unlink($cacheFile);
        return null;
    }
    
    /**
     * Set rate limit cache
     */
    private function setCachedRateLimit($key, $count) {
        $cacheFile = __DIR__ . "/../../sessions/rate_limit_{$key}.json";
        $data = [
            'count' => $count,
            'expires' => strtotime('+1 minute', mktime(date('H'), date('i'), 0)) // Next minute boundary
        ];
        
        file_put_contents($cacheFile, json_encode($data));
    }
} 