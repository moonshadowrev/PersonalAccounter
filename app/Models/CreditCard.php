<?php

require_once __DIR__ . '/Model.php';

class CreditCard extends Model {

    protected $table = 'credit_cards';

    public function getByUserId($userId) {
        return $this->db->select('credit_cards', '*', ['user_id' => $userId]);
    }

    public function find($id) {
        return $this->db->get('credit_cards', '*', ['id' => $id]);
    }

    public function create($data) {
        return $this->db->insert('credit_cards', $data);
    }

    public function update($id, $data) {
        return $this->db->update('credit_cards', $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('credit_cards', ['id' => $id]);
    }

    /**
     * Get all credit cards with user information (for admin access)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('credit_cards', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'credit_cards.id',
                'credit_cards.name',
                'credit_cards.card_number_last4',
                'credit_cards.expiry_month',
                'credit_cards.expiry_year',
                'credit_cards.created_at',
                'credit_cards.updated_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['credit_cards.created_at' => 'DESC']
            ]);
        }, []); // Return empty array as fallback
    }

    /**
     * Get all credit cards with filters (admin access)
     */
    public function getAllWithFilters($filters = [], $page = 1, $limit = 20) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters, $page, $limit) {
            $conditions = [];
            $joins = [
                '[>]users' => ['user_id' => 'id']
            ];
            
            // Apply filters
            if (!empty($filters['expiry_year'])) {
                $conditions['credit_cards.expiry_year'] = $filters['expiry_year'];
            }
            
            if (!empty($filters['expiry_month'])) {
                $conditions['credit_cards.expiry_month'] = $filters['expiry_month'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'credit_cards.name[~]' => $filters['search'],
                    'credit_cards.card_number_last4[~]' => $filters['search']
                ];
            }
            
            // Pagination
            $offset = ($page - 1) * $limit;
            $conditions['LIMIT'] = [$offset, $limit];
            $conditions['ORDER'] = ['credit_cards.created_at' => 'DESC'];
            
            $columns = [
                'credit_cards.id',
                'credit_cards.name',
                'credit_cards.card_number_last4',
                'credit_cards.expiry_month',
                'credit_cards.expiry_year',
                'credit_cards.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ];
            
            return $this->db->select('credit_cards', $joins, $columns, $conditions);
        }, []);
    }


} 