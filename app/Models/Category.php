<?php

require_once __DIR__ . '/Model.php';

class Category extends Model {

    protected $table = 'categories';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('categories', '*', [
                'user_id' => $userId,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('categories', '*', ['id' => $id]);
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            $result = $this->db->insert('categories', $data);
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            $result = $this->db->update('categories', $data, ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $result = $this->db->delete('categories', ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    /**
     * Check if category name exists for user
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
            
            $category = $this->db->get('categories', 'id', $conditions);
            return !empty($category);
        }, false);
    }

    /**
     * Get categories with expense count
     */
    public function getCategoriesWithExpenseCount($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            // First get all categories for the user
            $categories = $this->db->select('categories', '*', [
                'user_id' => $userId,
                'ORDER' => ['name' => 'ASC']
            ]);
            
            // Then add expense count for each category
            foreach ($categories as &$category) {
                $expenseCount = $this->db->count('expenses', [
                    'category_id' => $category['id']
                ]);
                $category['expense_count'] = $expenseCount;
            }
            
            return $categories;
        }, []);
    }

    /**
     * Search categories by name (admin access - across all users)
     */
    public function searchByName($query, $userId = null, $limit = 10) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($query, $userId, $limit) {
            $conditions = [
                'categories.name[~]' => $query,
                'ORDER' => ['categories.name' => 'ASC'],
                'LIMIT' => $limit
            ];
            
            $joins = ['[>]users' => ['user_id' => 'id']];
            $columns = [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color', 
                'categories.icon',
                'categories.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ];
            
            // For centralized admin system, search all categories by default
            // Only filter by user if specifically requested (legacy support)
            if ($userId !== null) {
                $conditions['categories.user_id'] = $userId;
            }
            
            return $this->db->select('categories', $joins, $columns, $conditions);
        }, []);
    }

    /**
     * Get popular categories (most used)
     */
    public function getPopularCategories($userId, $limit = 5) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $limit) {
            // Get categories with their expense counts
            $categories = $this->getCategoriesWithExpenseCount($userId);
            
            // Filter out categories with no expenses and sort by count
            $categories = array_filter($categories, function($category) {
                return $category['expense_count'] > 0;
            });
            
            // Sort by expense count descending
            usort($categories, function($a, $b) {
                return $b['expense_count'] - $a['expense_count'];
            });
            
            // Limit results
            return array_slice($categories, 0, $limit);
        }, []);
    }

    /**
     * Get default categories for new users
     */
    public static function getDefaultCategories() {
        return [
            ['name' => 'General', 'description' => 'General expenses', 'color' => '#3B82F6', 'icon' => 'fas fa-folder']
        ];
    }

    /**
     * Create default categories for a user
     */
    public function createDefaultCategories($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            $defaultCategories = self::getDefaultCategories();
            $created = 0;
            
            foreach ($defaultCategories as $category) {
                $categoryData = array_merge($category, [
                    'user_id' => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $result = $this->db->insert('categories', $categoryData);
                if ($result) {
                    $created++;
                }
            }
            
            return $created;
        }, 0);
    }

    /**
     * Get all categories with expense count and user info (for centralized system)
     */
    public function getAllCategoriesWithExpenseCountAndUser() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            // First get all categories with user info
            $categories = $this->db->select('categories', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color',
                'categories.icon',
                'categories.created_at',
                'categories.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'ORDER' => ['categories.name' => 'ASC']
            ]);
            
            // Then add expense count for each category
            foreach ($categories as &$category) {
                $expenseCount = $this->db->count('expenses', [
                    'category_id' => $category['id']
                ]);
                $category['expense_count'] = $expenseCount;
            }
            
            return $categories;
        }, []);
    }

    /**
     * Get all categories with user info (for admin access and AJAX endpoints)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('categories', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color',
                'categories.icon',
                'categories.created_at',
                'categories.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'ORDER' => ['categories.name' => 'ASC']
            ]);
        }, []);
    }
} 