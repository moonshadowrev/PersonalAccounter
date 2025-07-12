<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../Services/ErrorHandler.php';

class Transaction extends Model {

    protected $table = 'transactions';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('transactions', '*', [
                'user_id' => $userId,
                'ORDER' => ['transaction_date' => 'DESC']
            ]);
        }, []);
    }

    public function getBySubscriptionId($subscriptionId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($subscriptionId) {
            return $this->db->select('transactions', '*', [
                'subscription_id' => $subscriptionId,
                'ORDER' => ['transaction_date' => 'DESC']
            ]);
        }, []);
    }

    public function getByExpenseId($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            return $this->db->select('transactions', '*', [
                'expense_id' => $expenseId,
                'ORDER' => ['transaction_date' => 'DESC']
            ]);
        }, []);
    }

    public function getByUserIdWithDateFilter($userId, $from_date, $to_date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $from_date, $to_date) {
            return $this->db->select('transactions', '*', [
                'user_id' => $userId,
                'transaction_date[>=]' => $from_date . ' 00:00:00',
                'transaction_date[<=]' => $to_date . ' 23:59:59',
                'ORDER' => ['transaction_date' => 'DESC']
            ]);
        }, []);
    }

    public function getAllWithDateFilter($from_date, $to_date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($from_date, $to_date) {
            return $this->db->select('transactions', '*', [
                'transaction_date[>=]' => $from_date . ' 00:00:00',
                'transaction_date[<=]' => $to_date . ' 23:59:59',
                'ORDER' => ['transaction_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all transactions with complete information (unified for both expenses and subscriptions)
     */
    public function getAllWithCompleteInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('transactions', [
                '[>]users' => ['user_id' => 'id'],
                '[>]subscriptions' => ['subscription_id' => 'id'],
                '[>]expenses' => ['expense_id' => 'id'],
                '[>]categories' => ['expenses.category_id' => 'id'],
                '[>]credit_cards' => ['credit_card_id' => 'id'],
                '[>]bank_accounts' => ['bank_account_id' => 'id'],
                '[>]crypto_wallets' => ['crypto_wallet_id' => 'id']
            ], [
                'transactions.id',
                'transactions.amount',
                'transactions.currency',
                'transactions.transaction_date',
                'transactions.status',
                'transactions.transaction_type',
                'transactions.payment_method_type',
                'transactions.reference_number',
                'transactions.description',
                'transactions.notes',
                'transactions.created_at',
                'users.name(user_name)',
                'users.email(user_email)',
                'subscriptions.name(subscription_name)',
                'subscriptions.billing_cycle',
                'expenses.title(expense_title)',
                'expenses.vendor(expense_vendor)',
                'categories.name(category_name)',
                'categories.color(category_color)',
                'credit_cards.name(credit_card_name)',
                'bank_accounts.name(bank_account_name)',
                'crypto_wallets.name(crypto_wallet_name)'
            ], [
                'ORDER' => ['transactions.transaction_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all transactions with complete information filtered by date
     */
    public function getAllWithCompleteInfoAndDateFilter($from_date, $to_date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($from_date, $to_date) {
            return $this->db->select('transactions', [
                '[>]users' => ['user_id' => 'id'],
                '[>]subscriptions' => ['subscription_id' => 'id'],
                '[>]expenses' => ['expense_id' => 'id'],
                '[>]categories' => ['expenses.category_id' => 'id'],
                '[>]credit_cards' => ['credit_card_id' => 'id'],
                '[>]bank_accounts' => ['bank_account_id' => 'id'],
                '[>]crypto_wallets' => ['crypto_wallet_id' => 'id']
            ], [
                'transactions.id',
                'transactions.amount',
                'transactions.currency',
                'transactions.transaction_date',
                'transactions.status',
                'transactions.transaction_type',
                'transactions.payment_method_type',
                'transactions.reference_number',
                'transactions.description',
                'transactions.notes',
                'transactions.created_at',
                'users.name(user_name)',
                'users.email(user_email)',
                'subscriptions.name(subscription_name)',
                'subscriptions.billing_cycle',
                'expenses.title(expense_title)',
                'expenses.vendor(expense_vendor)',
                'categories.name(category_name)',
                'categories.color(category_color)',
                'credit_cards.name(credit_card_name)',
                'bank_accounts.name(bank_account_name)',
                'crypto_wallets.name(crypto_wallet_name)'
            ], [
                'transactions.transaction_date[>=]' => $from_date . ' 00:00:00',
                'transactions.transaction_date[<=]' => $to_date . ' 23:59:59',
                'ORDER' => ['transactions.transaction_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get unified transaction statistics for both expenses and subscriptions
     */
    public function getTransactionStats($from_date = null, $to_date = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($from_date, $to_date) {
            $conditions = [];
            
            if ($from_date && $to_date) {
                $conditions['transaction_date[>=]'] = $from_date . ' 00:00:00';
                $conditions['transaction_date[<=]'] = $to_date . ' 23:59:59';
            }
            
            // Total transactions
            $totalTransactions = $this->db->count('transactions', $conditions);
            
            // Successful transactions
            $successfulConditions = array_merge($conditions, ['status' => 'successful']);
            $successfulTransactions = $this->db->count('transactions', $successfulConditions);
            
            // Failed transactions
            $failedConditions = array_merge($conditions, ['status' => 'failed']);
            $failedTransactions = $this->db->count('transactions', $failedConditions);
            
            // Pending transactions
            $pendingConditions = array_merge($conditions, ['status' => 'pending']);
            $pendingTransactions = $this->db->count('transactions', $pendingConditions);
            
            // Total revenue (successful transactions only)
            $totalRevenue = (float) $this->db->sum('transactions', 'amount', $successfulConditions);
            
            // Calculate success rate
            $successRate = $totalTransactions > 0 ? round(($successfulTransactions / $totalTransactions) * 100, 2) : 0;
            
            // Transaction type breakdown
            $subscriptionTransactions = $this->db->count('transactions', array_merge($conditions, ['transaction_type' => 'subscription']));
            $expenseTransactions = $this->db->count('transactions', array_merge($conditions, ['transaction_type' => 'expense']));
            
            // Revenue by type
            $subscriptionRevenue = (float) $this->db->sum('transactions', 'amount', array_merge($successfulConditions, ['transaction_type' => 'subscription']));
            $expenseRevenue = (float) $this->db->sum('transactions', 'amount', array_merge($successfulConditions, ['transaction_type' => 'expense']));
            
            return [
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'failed_transactions' => $failedTransactions,
                'pending_transactions' => $pendingTransactions,
                'total_revenue' => $totalRevenue,
                'success_rate' => $successRate,
                'subscription_transactions' => $subscriptionTransactions,
                'expense_transactions' => $expenseTransactions,
                'subscription_revenue' => $subscriptionRevenue,
                'expense_revenue' => $expenseRevenue
            ];
        }, [
            'total_transactions' => 0,
            'successful_transactions' => 0,
            'failed_transactions' => 0,
            'pending_transactions' => 0,
            'total_revenue' => 0,
            'success_rate' => 0,
            'subscription_transactions' => 0,
            'expense_transactions' => 0,
            'subscription_revenue' => 0,
            'expense_revenue' => 0
        ]);
    }

    public function getMonthlyTransactionData($year = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($year) {
            $year = $year ?: date('Y');
            
            $transactions = $this->db->select('transactions', [
                'amount',
                'transaction_date',
                'status',
                'transaction_type'
            ], [
                'transaction_date[>=]' => $year . '-01-01 00:00:00',
                'transaction_date[<=]' => $year . '-12-31 23:59:59',
                'status' => 'successful'
            ]);
            
            $monthlyData = [
                'subscriptions' => array_fill(1, 12, 0),
                'expenses' => array_fill(1, 12, 0),
                'total' => array_fill(1, 12, 0)
            ];
            
            foreach ($transactions as $transaction) {
                $month = (int)date('n', strtotime($transaction['transaction_date']));
                $amount = floatval($transaction['amount']);
                
                $monthlyData['total'][$month] += $amount;
                
                if ($transaction['transaction_type'] === 'subscription') {
                    $monthlyData['subscriptions'][$month] += $amount;
                } else {
                    $monthlyData['expenses'][$month] += $amount;
                }
            }
            
            return $monthlyData;
        }, [
            'subscriptions' => array_fill(1, 12, 0),
            'expenses' => array_fill(1, 12, 0),
            'total' => array_fill(1, 12, 0)
        ]);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('transactions', '*', ['id' => $id]);
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            $result = $this->db->insert('transactions', $data);
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            $result = $this->db->update('transactions', $data, ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $result = $this->db->delete('transactions', ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function count($where = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($where) {
            return $this->db->count('transactions', $where);
        }, 0);
    }

    /**
     * Get all transactions with user information (for admin access)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('transactions', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'transactions.id',
                'transactions.amount',
                'transactions.currency',
                'transactions.transaction_date',
                'transactions.description',
                'transactions.reference_number',
                'transactions.status',
                'transactions.transaction_type',
                'transactions.payment_method_type',
                'transactions.created_at',
                'transactions.updated_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['transactions.transaction_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all transactions with filters (admin access)
     */
    public function getAllWithFilters($filters = [], $page = 1, $limit = 50) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters, $page, $limit) {
            $conditions = [];
            $joins = [
                '[>]users' => ['user_id' => 'id']
            ];
            
            // Apply filters
            if (!empty($filters['transaction_type'])) {
                $conditions['transactions.transaction_type'] = $filters['transaction_type'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['transactions.status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method_type'])) {
                $conditions['transactions.payment_method_type'] = $filters['payment_method_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['transactions.transaction_date[>=]'] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['transactions.transaction_date[<=]'] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['transactions.amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['transactions.amount[<=]'] = $filters['amount_max'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'transactions.description[~]' => $filters['search'],
                    'transactions.reference_number[~]' => $filters['search'],
                    'transactions.notes[~]' => $filters['search']
                ];
            }
            
            // Pagination
            $offset = ($page - 1) * $limit;
            $conditions['LIMIT'] = [$offset, $limit];
            $conditions['ORDER'] = ['transactions.transaction_date' => 'DESC'];
            
            $columns = [
                'transactions.id',
                'transactions.amount',
                'transactions.currency',
                'transactions.transaction_date',
                'transactions.description',
                'transactions.reference_number',
                'transactions.status',
                'transactions.transaction_type',
                'transactions.payment_method_type',
                'transactions.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ];
            
            return $this->db->select('transactions', $joins, $columns, $conditions);
        }, []);
    }

    /**
     * Create transaction for subscription payment
     */
    public function createSubscriptionTransaction($subscriptionData, $amount, $status = 'successful') {
        $data = [
            'user_id' => $subscriptionData['user_id'],
            'subscription_id' => $subscriptionData['id'],
            'expense_id' => null,
            'credit_card_id' => $subscriptionData['credit_card_id'],
            'bank_account_id' => $subscriptionData['bank_account_id'] ?? null,
            'crypto_wallet_id' => $subscriptionData['crypto_wallet_id'] ?? null,
            'amount' => $amount,
            'currency' => $subscriptionData['currency'],
            'transaction_date' => date('Y-m-d H:i:s'),
            'status' => $status,
            'payment_method_type' => $subscriptionData['payment_method_type'] ?? 'credit_card',
            'transaction_type' => 'subscription',
            'description' => 'Subscription payment: ' . $subscriptionData['name'],
            'reference_number' => 'SUB-' . $subscriptionData['id'] . '-' . date('YmdHis')
        ];
        
        return $this->create($data);
    }

    /**
     * Create transaction for expense payment
     */
    public function createExpenseTransaction($expenseData, $status = 'successful') {
        $data = [
            'user_id' => $expenseData['user_id'],
            'subscription_id' => null,
            'expense_id' => $expenseData['id'],
            'credit_card_id' => $expenseData['credit_card_id'],
            'bank_account_id' => $expenseData['bank_account_id'],
            'crypto_wallet_id' => $expenseData['crypto_wallet_id'],
            'amount' => $expenseData['amount'],
            'currency' => $expenseData['currency'],
            'transaction_date' => $expenseData['expense_date'] . ' ' . date('H:i:s'),
            'status' => $status,
            'payment_method_type' => $expenseData['payment_method_type'],
            'transaction_type' => 'expense',
            'description' => 'Expense payment: ' . $expenseData['title'],
            'reference_number' => 'EXP-' . $expenseData['id'] . '-' . date('YmdHis'),
            'notes' => $expenseData['notes']
        ];
        
        return $this->create($data);
    }
} 