<?php

require_once __DIR__ . '/Model.php';

class Subscription extends Model {

    protected $table = 'subscriptions';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('subscriptions', '*', ['user_id' => $userId]);
        }, []); // Return empty array as fallback
    }

    public function getByUserIdWithDateFilter($userId, $from_date, $to_date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $from_date, $to_date) {
            return $this->db->select('subscriptions', '*', [
                'user_id' => $userId,
                'created_at[>=]' => $from_date . ' 00:00:00',
                'created_at[<=]' => $to_date . ' 23:59:59',
                'ORDER' => ['created_at' => 'DESC']
            ]);
        }, []); // Return empty array as fallback
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('subscriptions', '*', ['id' => $id]);
        }, null); // Return null as fallback
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            $result = $this->db->insert('subscriptions', $data);
            // Medoo returns PDOStatement for insert operations
            // Get the last inserted ID if successful
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false); // Return false as fallback
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            $result = $this->db->update('subscriptions', $data, ['id' => $id]);
            // Medoo returns PDOStatement for update operations
            // Check if any rows were affected
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false); // Return false as fallback
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $result = $this->db->delete('subscriptions', ['id' => $id]);
            // Medoo returns PDOStatement for delete operations
            // Check if any rows were affected
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false); // Return false as fallback
    }

    /**
     * Get all subscriptions with pagination
     */
    public function getAllPaginated($limit = 10, $offset = 0) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($limit, $offset) {
            return $this->db->select('subscriptions', '*', [
                'LIMIT' => [$offset, $limit],
                'ORDER' => ['created_at' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Count total subscriptions
     */
    public function count($where = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($where) {
            return $this->db->count('subscriptions', $where);
        }, 0);
    }

    /**
     * Get subscriptions by status
     */
    public function getByStatus($status) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($status) {
            return $this->db->select('subscriptions', '*', ['status' => $status]);
        }, []);
    }

    /**
     * Get all subscriptions with date filter (no user filtering)
     */
    public function getAllWithDateFilter($from_date, $to_date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($from_date, $to_date) {
            return $this->db->select('subscriptions', '*', [
                'created_at[>=]' => $from_date . ' 00:00:00',
                'created_at[<=]' => $to_date . ' 23:59:59',
                'ORDER' => ['created_at' => 'DESC']
            ]);
        }, []); // Return empty array as fallback
    }

    /**
     * Get all subscriptions with user information (for admin access)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('subscriptions', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'subscriptions.id',
                'subscriptions.name',
                'subscriptions.description',
                'subscriptions.amount',
                'subscriptions.currency',
                'subscriptions.billing_cycle',
                'subscriptions.next_payment_date',
                'subscriptions.status',
                'subscriptions.created_at',
                'subscriptions.updated_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['subscriptions.created_at' => 'DESC']
            ]);
        }, []); // Return empty array as fallback
    }

    /**
     * Get all subscriptions with filters (admin access)
     */
    public function getAllWithFilters($filters = [], $page = 1, $limit = 20) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters, $page, $limit) {
            $conditions = [];
            $joins = [
                '[>]users' => ['user_id' => 'id']
            ];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions['subscriptions.status'] = $filters['status'];
            }
            
            if (!empty($filters['billing_cycle'])) {
                $conditions['subscriptions.billing_cycle'] = $filters['billing_cycle'];
            }
            
            if (!empty($filters['currency'])) {
                $conditions['subscriptions.currency'] = $filters['currency'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['subscriptions.next_payment_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['subscriptions.next_payment_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['subscriptions.amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['subscriptions.amount[<=]'] = $filters['amount_max'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'subscriptions.name[~]' => $filters['search'],
                    'subscriptions.description[~]' => $filters['search']
                ];
            }
            
            // Pagination
            $offset = ($page - 1) * $limit;
            $conditions['LIMIT'] = [$offset, $limit];
            $conditions['ORDER'] = ['subscriptions.created_at' => 'DESC'];
            
            $columns = [
                'subscriptions.id',
                'subscriptions.name',
                'subscriptions.description',
                'subscriptions.amount',
                'subscriptions.currency',
                'subscriptions.billing_cycle',
                'subscriptions.next_payment_date',
                'subscriptions.status',
                'subscriptions.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ];
            
            return $this->db->select('subscriptions', $joins, $columns, $conditions);
        }, []);
    }

    /**
     * Get subscription statistics across all users (admin access)
     */
    public function getAllSubscriptionStats() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            $totalSubscriptions = $this->db->count('subscriptions');
            $activeSubscriptions = $this->db->count('subscriptions', ['status' => 'active']);
            $cancelledSubscriptions = $this->db->count('subscriptions', ['status' => 'cancelled']);
            $totalAmount = $this->db->sum('subscriptions', 'amount', ['status' => 'active']) ?: 0;
            
            // Get subscription counts by billing cycle
            $cycleStats = $this->db->select('subscriptions', [
                'billing_cycle',
                'count' => 'COUNT(*)'
            ], [
                'GROUP' => 'billing_cycle',
                'ORDER' => ['count' => 'DESC']
            ]);
            
            return [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'cancelled_subscriptions' => $cancelledSubscriptions,
                'total_monthly_value' => floatval($totalAmount),
                'cycle_breakdown' => $cycleStats
            ];
        }, [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'cancelled_subscriptions' => 0,
            'total_monthly_value' => 0,
            'cycle_breakdown' => []
        ]);
    }


} 