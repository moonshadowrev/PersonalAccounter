<?php

require_once __DIR__ . '/Model.php';

class Tag extends Model {

    protected $table = 'tags';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('tags', '*', [
                'user_id' => $userId,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('tags', '*', ['id' => $id]);
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            $result = $this->db->insert('tags', $data);
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            $result = $this->db->update('tags', $data, ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            // First remove tag associations
            $this->db->delete('expense_tags', ['tag_id' => $id]);
            
            // Then delete the tag
            $result = $this->db->delete('tags', ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    /**
     * Check if tag name exists for user
     */
    public function nameExistsForUser($name, $userId, $excludeId = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($name, $userId, $excludeId) {
            $conditions = [
                'name' => $name,
                'user_id' => $userId
            ];
            
            if ($excludeId) {
                $conditions['id[!]'] = $excludeId;
            }
            
            $tag = $this->db->get('tags', 'id', $conditions);
            return !empty($tag);
        }, false);
    }

    /**
     * Get tags with expense count
     */
    public function getTagsWithExpenseCount($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            // First get all tags for the user
            $tags = $this->db->select('tags', '*', [
                'user_id' => $userId,
                'ORDER' => ['name' => 'ASC']
            ]);
            
            // Then add expense count for each tag
            foreach ($tags as &$tag) {
                $expenseCount = $this->db->count('expense_tags', [
                    'tag_id' => $tag['id']
                ]);
                $tag['expense_count'] = $expenseCount;
            }
            
            return $tags;
        }, []);
    }

    /**
     * Get popular tags (most used)
     */
    public function getPopularTags($userId, $limit = 10) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $limit) {
            if ($userId) {
                // Get tags with their expense counts for specific user
                $tags = $this->getTagsWithExpenseCount($userId);
            } else {
                // Get all tags with their expense counts for centralized system
                $tags = $this->getAllTagsWithExpenseCountAndUser();
            }
            
            // Filter out tags with no expenses and sort by count
            $tags = array_filter($tags, function($tag) {
                return $tag['expense_count'] > 0;
            });
            
            // Sort by expense count descending
            usort($tags, function($a, $b) {
                return $b['expense_count'] - $a['expense_count'];
            });
            
            // Limit results
            return array_slice($tags, 0, $limit);
        }, []);
    }

    /**
     * Get tags for a specific expense
     */
    public function getTagsForExpense($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            // First get the tag IDs for this expense
            $tagIds = $this->db->select('expense_tags', 'tag_id', [
                'expense_id' => $expenseId
            ]);
            
            if (empty($tagIds)) {
                return [];
            }
            
            // Then get the tag details
            return $this->db->select('tags', [
                'id',
                'name',
                'color',
                'description'
            ], [
                'id' => $tagIds,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Attach tags to an expense
     */
    public function attachToExpense($expenseId, $tagIds) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId, $tagIds) {
            // First remove existing tags
            $this->db->delete('expense_tags', ['expense_id' => $expenseId]);
            
            // Add new tags
            $attached = 0;
            foreach ($tagIds as $tagId) {
                $result = $this->db->insert('expense_tags', [
                    'expense_id' => $expenseId,
                    'tag_id' => $tagId
                ]);
                if ($result) {
                    $attached++;
                }
            }
            
            return $attached;
        }, 0);
    }

    /**
     * Detach tags from an expense
     */
    public function detachFromExpense($expenseId, $tagIds = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId, $tagIds) {
            $conditions = ['expense_id' => $expenseId];
            
            if ($tagIds !== null) {
                $conditions['tag_id'] = $tagIds;
            }
            
            $result = $this->db->delete('expense_tags', $conditions);
            return $result ? $result->rowCount() : 0;
        }, 0);
    }

    /**
     * Get default tags for new users
     */
    public static function getDefaultTags() {
        return [
            ['name' => 'General', 'description' => 'General expenses', 'color' => '#3B82F6']
        ];
    }

    /**
     * Create default tags for a user
     */
    public function createDefaultTags($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            $defaultTags = self::getDefaultTags();
            $created = 0;
            
            foreach ($defaultTags as $tag) {
                $tagData = array_merge($tag, [
                    'user_id' => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $result = $this->db->insert('tags', $tagData);
                if ($result) {
                    $created++;
                }
            }
            
            return $created;
        }, 0);
    }

    /**
     * Search tags by name
     */
    public function searchByName($userId, $query, $limit = 10) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $query, $limit) {
            $conditions = [
                'name[~]' => $query,
                'ORDER' => ['name' => 'ASC'],
                'LIMIT' => $limit
            ];
            
            // If userId is provided, filter by user, otherwise search all for centralized system
            if ($userId) {
                $conditions['user_id'] = $userId;
            }
            
            return $this->db->select('tags', '*', $conditions);
        }, []);
    }

    /**
     * Get all tags with expense count (admin access across all users)
     */
    public function getAllTagsWithExpenseCount() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            $tags = $this->db->select('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description', 
                'tags.color',
                'tags.created_at',
                'tags.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'ORDER' => ['tags.name' => 'ASC']
            ]);
            
            // Add expense count for each tag
            foreach ($tags as &$tag) {
                $expenseCount = $this->db->count('expense_tags', [
                    'tag_id' => $tag['id']
                ]);
                $tag['expense_count'] = $expenseCount;
            }
            
            return $tags;
        }, []);
    }

    /**
     * Get all tags with expense count and user info (for centralized system)
     */
    public function getAllTagsWithExpenseCountAndUser() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            // First get all tags with user info
            $tags = $this->db->select('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description',
                'tags.color',
                'tags.created_at',
                'tags.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'ORDER' => ['tags.name' => 'ASC']
            ]);
            
            // Then add expense count for each tag
            foreach ($tags as &$tag) {
                $expenseCount = $this->db->count('expense_tags', [
                    'tag_id' => $tag['id']
                ]);
                $tag['expense_count'] = $expenseCount;
            }
            
            return $tags;
        }, []);
    }

    /**
     * Get all tags with user info (for admin access and AJAX endpoints)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description',
                'tags.color',
                'tags.created_at',
                'tags.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'ORDER' => ['tags.name' => 'ASC']
            ]);
        }, []);
    }
} 