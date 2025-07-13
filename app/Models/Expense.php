<?php

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Tag.php';
require_once __DIR__ . '/Category.php';

class Expense extends Model {

    protected $table = 'expenses';
    private $tagModel;
    private $categoryModel;

    public function __construct($database) {
        parent::__construct($database);
        $this->tagModel = new Tag($database);
        $this->categoryModel = new Category($database);
    }

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'ORDER' => ['expense_date' => 'DESC', 'created_at' => 'DESC']
            ]);
        }, []);
    }

    public function getByUserIdWithRelations($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            $expenses = $this->db->select('expenses', [
                '[>]categories' => ['category_id' => 'id'],
                '[>]credit_cards' => ['credit_card_id' => 'id'],
                '[>]bank_accounts' => ['bank_account_id' => 'id'],
                '[>]crypto_wallets' => ['crypto_wallet_id' => 'id']
            ], [
                'expenses.id',
                'expenses.title',
                'expenses.description',
                'expenses.amount',
                'expenses.currency',
                'expenses.tax_amount',
                'expenses.tax_rate',
                'expenses.tax_type',
                'expenses.expense_date',
                'expenses.receipt_number',
                'expenses.vendor',
                'expenses.notes',
                'expenses.status',
                'expenses.payment_method_type',
                'expenses.attachments',
                'expenses.created_at',
                'categories.name(category_name)',
                'categories.color(category_color)',
                'categories.icon(category_icon)',
                'credit_cards.name(credit_card_name)',
                'bank_accounts.name(bank_account_name)',
                'crypto_wallets.name(crypto_wallet_name)'
            ], [
                'expenses.user_id' => $userId,
                'ORDER' => ['expenses.expense_date' => 'DESC', 'expenses.created_at' => 'DESC']
            ]);

            // Get tags for each expense
            foreach ($expenses as &$expense) {
                $expense['tags'] = $this->tagModel->getTagsForExpense($expense['id']);
            }

            return $expenses;
        }, []);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('expenses', '*', ['id' => $id]);
        }, null);
    }

    public function findWithRelations($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $expense = $this->db->get('expenses', [
                '[>]categories' => ['category_id' => 'id'],
                '[>]credit_cards' => ['credit_card_id' => 'id'],
                '[>]bank_accounts' => ['bank_account_id' => 'id'],
                '[>]crypto_wallets' => ['crypto_wallet_id' => 'id'],
                '[>]users' => ['user_id' => 'id']
            ], [
                'expenses.id',
                'expenses.user_id',
                'expenses.category_id',
                'expenses.credit_card_id',
                'expenses.bank_account_id',
                'expenses.crypto_wallet_id',
                'expenses.title',
                'expenses.description',
                'expenses.amount',
                'expenses.currency',
                'expenses.tax_amount',
                'expenses.tax_rate',
                'expenses.tax_type',
                'expenses.expense_date',
                'expenses.receipt_number',
                'expenses.vendor',
                'expenses.notes',
                'expenses.status',
                'expenses.payment_method_type',
                'expenses.attachments',
                'expenses.created_at',
                'expenses.updated_at',
                '(expenses.amount + COALESCE(expenses.tax_amount, 0))(total_amount)',
                'categories.name(category_name)',
                'categories.color(category_color)',
                'categories.icon(category_icon)',
                'credit_cards.name(credit_card_name)',
                'bank_accounts.name(bank_account_name)',
                'crypto_wallets.name(crypto_wallet_name)',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], [
                'expenses.id' => $id
            ]);

            if ($expense) {
                $expense['tags'] = $this->tagModel->getTagsForExpense($id);
            }

            return $expense;
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            // Separate tags from expense data
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            // Remove manual timestamps - let database handle them
            unset($data['created_at']);
            unset($data['updated_at']);

            $result = $this->db->insert('expenses', $data);
            if ($result) {
                $expenseId = $this->db->id();
                
                // Attach tags if provided
                if (!empty($tags)) {
                    $this->tagModel->attachToExpense($expenseId, $tags);
                }
                
                return $expenseId;
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            // Separate tags from expense data
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            // Remove manual timestamp - let database handle it
            unset($data['updated_at']);

            $result = $this->db->update('expenses', $data, ['id' => $id]);
            
            // Update tags if provided
            if ($tags !== null) {
                $this->tagModel->attachToExpense($id, $tags);
            }
            
            return $result && $result->rowCount() > 0;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            // Remove tag associations
            $this->tagModel->detachFromExpense($id);
            
            // Delete the expense
            $result = $this->db->delete('expenses', ['id' => $id]);
            return $result && $result->rowCount() > 0;
        }, false);
    }

    /**
     * Get expenses with date filter
     */
    public function getByUserIdWithDateFilter($userId, $fromDate, $toDate) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $fromDate, $toDate) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'expense_date[>=]' => $fromDate,
                'expense_date[<=]' => $toDate,
                'ORDER' => ['expense_date' => 'DESC', 'created_at' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get expenses by category
     */
    public function getByCategory($userId, $categoryId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $categoryId) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'category_id' => $categoryId,
                'ORDER' => ['expense_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get expenses by status
     */
    public function getByStatus($userId, $status) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $status) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'status' => $status,
                'ORDER' => ['expense_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get expense statistics
     */
    public function getExpenseStats($userId, $fromDate = null, $toDate = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $fromDate, $toDate) {
            $conditions = ['user_id' => $userId];
            
            if ($fromDate && $toDate) {
                $conditions['expense_date[>=]'] = $fromDate;
                $conditions['expense_date[<=]'] = $toDate;
            }

            $total = $this->db->count('expenses', $conditions);
            
            $pendingConditions = array_merge($conditions, ['status' => 'pending']);
            $pending = $this->db->count('expenses', $pendingConditions);
            
            $approvedConditions = array_merge($conditions, ['status' => 'approved']);
            $approved = $this->db->count('expenses', $approvedConditions);
            
            $paidConditions = array_merge($conditions, ['status' => 'paid']);
            $paid = $this->db->count('expenses', $paidConditions);

            $totalAmount = $this->db->sum('expenses', 'amount', $conditions) ?: 0;
            $totalTax = $this->db->sum('expenses', 'tax_amount', $conditions) ?: 0;

            return [
                'total_expenses' => $total,
                'pending_expenses' => $pending,
                'approved_expenses' => $approved,
                'paid_expenses' => $paid,
                'total_amount' => floatval($totalAmount),
                'total_tax' => floatval($totalTax),
                'total_with_tax' => floatval($totalAmount + $totalTax)
            ];
        }, [
            'total_expenses' => 0,
            'pending_expenses' => 0,
            'approved_expenses' => 0,
            'paid_expenses' => 0,
            'total_amount' => 0,
            'total_tax' => 0,
            'total_with_tax' => 0
        ]);
    }

    /**
     * Get monthly expense data
     */
    public function getMonthlyExpenseData($userId, $year = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $year) {
            $year = $year ?: date('Y');
            
            $expenses = $this->db->select('expenses', [
                'amount',
                'expense_date'
            ], [
                'user_id' => $userId,
                'expense_date[>=]' => $year . '-01-01',
                'expense_date[<=]' => $year . '-12-31',
                'status' => ['pending', 'approved', 'paid'] // Exclude rejected
            ]);
            
            $monthlyData = array_fill(1, 12, 0);
            
            foreach ($expenses as $expense) {
                $month = (int)date('n', strtotime($expense['expense_date']));
                $monthlyData[$month] += floatval($expense['amount']);
            }
            
            return $monthlyData;
        }, array_fill(1, 12, 0));
    }

    /**
     * Get category breakdown
     */
    public function getCategoryBreakdown($userId, $fromDate = null, $toDate = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $fromDate, $toDate) {
            $conditions = [
                'expenses.user_id' => $userId,
                'expenses.status' => ['pending', 'approved', 'paid']
            ];
            
            if ($fromDate && $toDate) {
                $conditions['expenses.expense_date[>=]'] = $fromDate;
                $conditions['expenses.expense_date[<=]'] = $toDate;
            }

            return $this->db->select('expenses', [
                '[>]categories' => ['category_id' => 'id']
            ], [
                'categories.name(category_name)',
                'categories.color(category_color)',
                'total_amount' => 'SUM(expenses.amount)',
                'expense_count' => 'COUNT(expenses.id)'
            ], array_merge($conditions, [
                'GROUP' => 'expenses.category_id',
                'ORDER' => ['total_amount' => 'DESC']
            ]));
        }, []);
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown($userId, $fromDate = null, $toDate = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $fromDate, $toDate) {
            $conditions = [
                'user_id' => $userId,
                'status' => ['pending', 'approved', 'paid']
            ];
            
            if ($fromDate && $toDate) {
                $conditions['expense_date[>=]'] = $fromDate;
                $conditions['expense_date[<=]'] = $toDate;
            }

            return $this->db->select('expenses', [
                'payment_method_type',
                'total_amount' => 'SUM(amount)',
                'expense_count' => 'COUNT(id)'
            ], array_merge($conditions, [
                'GROUP' => 'payment_method_type',
                'ORDER' => ['total_amount' => 'DESC']
            ]));
        }, []);
    }

    /**
     * Search expenses
     */
    public function search($userId, $query) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $query) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'OR' => [
                    'title[~]' => $query,
                    'description[~]' => $query,
                    'vendor[~]' => $query,
                    'receipt_number[~]' => $query,
                    'notes[~]' => $query
                ],
                'ORDER' => ['expense_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Generate transaction record for this expense
     */
    public function generateTransaction($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            $expense = $this->find($expenseId);
            if (!$expense) {
                return false;
            }

            require_once __DIR__ . '/../Services/TransactionService.php';
            $transactionService = new TransactionService($this->db);

            $result = $transactionService->processExpenseTransaction($expenseId, 'successful');
            
            return $result['success'] ? $result['transaction_id'] : false;
        }, false);
    }

    /**
     * Bulk import expenses from array
     */
    public function bulkImport($userId, $expensesData) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $expensesData) {
            $imported = 0;
            $errors = [];

            foreach ($expensesData as $index => $expenseData) {
                try {
                    // Add user_id and default values
                    $expenseData['user_id'] = $userId;
                    $expenseData['status'] = $expenseData['status'] ?? 'pending';
                    $expenseData['currency'] = $expenseData['currency'] ?? 'USD';
                    
                    if (empty($expenseData['title']) || empty($expenseData['amount'])) {
                        $errors[] = "Row " . ($index + 1) . ": Title and amount are required";
                        continue;
                    }

                    $expenseId = $this->create($expenseData);
                    if ($expenseId) {
                        // Add tags if any were found/created
                        if (!empty($expenseData['tags'])) {
                            $this->addTags($expenseId, $expenseData['tags']);
                        }
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Failed to create expense";
                    }
                } catch (Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            return [
                'imported' => $imported,
                'errors' => $errors,
                'total' => count($expensesData)
            ];
        }, ['imported' => 0, 'errors' => ['Database operation failed'], 'total' => 0]);
    }

    /**
     * Get recent expenses
     */
    public function getRecent($userId, $limit = 10) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $limit) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'ORDER' => ['created_at' => 'DESC'],
                'LIMIT' => $limit
            ]);
        }, []);
    }

    /**
     * Get pending expenses requiring approval
     */
    public function getPendingApproval($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('expenses', '*', [
                'user_id' => $userId,
                'status' => 'pending',
                'ORDER' => ['expense_date' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Calculate tax amount from rate
     */
    public static function calculateTaxAmount($amount, $taxRate) {
        if (!$taxRate || $taxRate <= 0) {
            return 0;
        }
        return round(($amount * $taxRate) / 100, 2);
    }

    /**
     * Get expense status options
     */
    public static function getStatusOptions() {
        return [
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected', 
            'paid' => 'Paid'
        ];
    }

    /**
     * Get expense status badge class
     */
    public static function getStatusBadgeClass($status) {
        $classes = [
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'paid' => 'bg-info'
        ];
        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Get expenses with filters
     */
    public function getExpensesWithFilters($userId, $filters = [], $page = 1, $limit = 20) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $filters, $page, $limit) {
            $conditions = ['expenses.user_id' => $userId];
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $conditions['expenses.category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['expenses.status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method'])) {
                $conditions['expenses.payment_method_type'] = $filters['payment_method'];
            }
            
            if (!empty($filters['credit_card_id'])) {
                $conditions['expenses.credit_card_id'] = $filters['credit_card_id'];
            }
            
            if (!empty($filters['bank_account_id'])) {
                $conditions['expenses.bank_account_id'] = $filters['bank_account_id'];
            }
            
            if (!empty($filters['crypto_wallet_id'])) {
                $conditions['expenses.crypto_wallet_id'] = $filters['crypto_wallet_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['expenses.expense_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['expenses.expense_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['expenses.amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['expenses.amount[<=]'] = $filters['amount_max'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'expenses.title[~]' => $filters['search'],
                    'expenses.description[~]' => $filters['search'],
                    'expenses.notes[~]' => $filters['search']
                ];
            }
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Get expenses with relations
            return $this->db->select('expenses', [
                '[>]categories' => ['category_id' => 'id'],
            ], [
                'expenses.id',
                'expenses.user_id',
                'expenses.title',
                'expenses.description',
                'expenses.amount',
                'expenses.currency',
                'expenses.tax_amount',
                'expenses.tax_rate',
                '(expenses.amount + COALESCE(expenses.tax_amount, 0))(total_amount)',
                'expenses.category_id',
                'expenses.payment_method_type',
                'expenses.credit_card_id',
                'expenses.bank_account_id',
                'expenses.crypto_wallet_id',
                'expenses.expense_date',
                'expenses.status',
                'expenses.notes',
                'expenses.attachments',
                'expenses.created_at',
                'expenses.updated_at',
                'categories.name(category_name)',
                'categories.color(category_color)',
                'categories.icon(category_icon)'
            ], array_merge($conditions, [
                'ORDER' => ['expenses.expense_date' => 'DESC', 'expenses.created_at' => 'DESC'],
                'LIMIT' => [$offset, $limit]
            ]));
        }, []);
    }

    /**
     * Count expenses with filters
     */
    public function countExpensesWithFilters($userId, $filters = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $filters) {
            $conditions = ['expenses.user_id' => $userId];
            
            // Apply filters (same as getExpensesWithFilters)
            if (!empty($filters['category_id'])) {
                $conditions['expenses.category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['expenses.status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method'])) {
                $conditions['expenses.payment_method_type'] = $filters['payment_method'];
            }
            
            if (!empty($filters['credit_card_id'])) {
                $conditions['expenses.credit_card_id'] = $filters['credit_card_id'];
            }
            
            if (!empty($filters['bank_account_id'])) {
                $conditions['expenses.bank_account_id'] = $filters['bank_account_id'];
            }
            
            if (!empty($filters['crypto_wallet_id'])) {
                $conditions['expenses.crypto_wallet_id'] = $filters['crypto_wallet_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['expenses.expense_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['expenses.expense_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['expenses.amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['expenses.amount[<=]'] = $filters['amount_max'];
            }
            
            // Search filter
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'expenses.title[~]' => $filters['search'],
                    'expenses.description[~]' => $filters['search'],
                    'expenses.notes[~]' => $filters['search']
                ];
            }
            
            return $this->db->count('expenses', $conditions);
        }, 0);
    }

    /**
     * Get expense tags
     */
    public function getExpenseTags($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            return $this->db->select('expense_tags', [
                '[>]tags' => ['tag_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.color'
            ], [
                'expense_tags.expense_id' => $expenseId
            ]);
        }, []);
    }

    /**
     * Add tags to expense
     */
    public function addTags($expenseId, $tagIds) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId, $tagIds) {
            foreach ($tagIds as $tagId) {
                $this->db->insert('expense_tags', [
                    'expense_id' => $expenseId,
                    'tag_id' => $tagId
                ]);
            }
            return true;
        }, false);
    }

    /**
     * Remove tags from expense
     */
    public function removeTags($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            return $this->db->delete('expense_tags', [
                'expense_id' => $expenseId
            ]);
        }, false);
    }

    /**
     * Approve expense
     */
    public function approve($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            $result = $this->db->update('expenses', [
                'status' => 'approved'
            ], ['id' => $expenseId]);
            
            if ($result) {
                $this->generateTransaction($expenseId);
            }
            
            return $result;
        }, false);
    }

    /**
     * Reject expense
     */
    public function reject($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            $result = $this->db->update('expenses', [
                'status' => 'rejected'
            ], ['id' => $expenseId]);
            
            if ($result) {
                $this->removeTransaction($expenseId);
            }
            
            return $result;
        }, false);
    }

    /**
     * Remove transaction for expense
     */
    public function removeTransaction($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            return $this->db->delete('transactions', [
                'expense_id' => $expenseId
            ]);
        }, false);
    }

    /**
     * Get transaction for expense
     */
    public function getTransaction($expenseId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($expenseId) {
            return $this->db->get('transactions', '*', [
                'expense_id' => $expenseId
            ]);
        }, null);
    }

    /**
     * Get analytics for expenses
     */
    public function getAnalytics($userId, $period = 'month') {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $period) {
            $now = new DateTime();
            
            switch ($period) {
                case 'week':
                    $fromDate = $now->modify('-7 days')->format('Y-m-d');
                    break;
                case 'quarter':
                    $fromDate = $now->modify('-3 months')->format('Y-m-d');
                    break;
                case 'year':
                    $fromDate = $now->modify('-1 year')->format('Y-m-d');
                    break;
                default: // month
                    $fromDate = $now->modify('-1 month')->format('Y-m-d');
                    break;
            }
            
            $toDate = date('Y-m-d');
            
            $stats = $this->getExpenseStats($userId, $fromDate, $toDate);
            $categoryBreakdown = $this->getCategoryBreakdown($userId, $fromDate, $toDate);
            $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($userId, $fromDate, $toDate);
            
            return [
                'period' => $period,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'stats' => $stats,
                'category_breakdown' => $categoryBreakdown,
                'payment_method_breakdown' => $paymentMethodBreakdown
            ];
        }, []);
    }

    /**
     * Get expenses by category ID
     */
    public function getByCategoryId($categoryId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($categoryId) {
            return $this->db->select('expenses', '*', [
                'category_id' => $categoryId,
                'ORDER' => ['expense_date' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all expenses with filters (for centralized system)
     */
    public function getAllExpensesWithFilters($filters = [], $page = 1, $limit = 20) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters, $page, $limit) {
            $conditions = [];
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $conditions['category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method'])) {
                $conditions['payment_method_type'] = $filters['payment_method'];
            }
            
            if (!empty($filters['payment_id'])) {
                switch ($filters['payment_method']) {
                    case 'credit_card':
                        $conditions['credit_card_id'] = $filters['payment_id'];
                        break;
                    case 'bank_account':
                        $conditions['bank_account_id'] = $filters['payment_id'];
                        break;
                    case 'crypto_wallet':
                        $conditions['crypto_wallet_id'] = $filters['payment_id'];
                        break;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['expense_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['expense_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['amount[<=]'] = $filters['amount_max'];
            }
            
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'title[~]' => $filters['search'],
                    'description[~]' => $filters['search'],
                    'vendor[~]' => $filters['search'],
                    'receipt_number[~]' => $filters['search']
                ];
            }
            
            // Pagination
            $offset = ($page - 1) * $limit;
            $conditions['ORDER'] = ['expense_date' => 'DESC', 'created_at' => 'DESC'];
            $conditions['LIMIT'] = [$offset, $limit];
            
            // Get expenses without JOINs to avoid filtering issues
            $expenses = $this->db->select('expenses', [
                'id',
                'user_id',
                'category_id',
                'credit_card_id',
                'bank_account_id',
                'crypto_wallet_id',
                'title',
                'description',
                'amount',
                'currency',
                'tax_amount',
                'tax_rate',
                'tax_type',
                'expense_date',
                'receipt_number',
                'vendor',
                'notes',
                'status',
                'payment_method_type',
                'attachments',
                'created_at'
            ], $conditions);
            
            // Add related data for each expense
            foreach ($expenses as &$expense) {
                // Initialize default values
                $expense['category_name'] = null;
                $expense['category_color'] = null;
                $expense['category_icon'] = null;
                $expense['creator_name'] = null;
                $expense['creator_email'] = null;
                $expense['credit_card_name'] = null;
                $expense['bank_account_name'] = null;
                $expense['crypto_wallet_name'] = null;
                
                // Get category info
                if (!empty($expense['category_id'])) {
                    $category = $this->db->get('categories', ['name', 'color', 'icon'], ['id' => $expense['category_id']]);
                    if ($category) {
                        $expense['category_name'] = $category['name'];
                        $expense['category_color'] = $category['color'];
                        $expense['category_icon'] = $category['icon'];
                    }
                }
                
                // Get user info
                if (!empty($expense['user_id'])) {
                    $user = $this->db->get('users', ['name', 'email'], ['id' => $expense['user_id']]);
                    if ($user) {
                        $expense['creator_name'] = $user['name'];
                        $expense['creator_email'] = $user['email'];
                    }
                }
                
                // Get payment method names
                if (!empty($expense['credit_card_id'])) {
                    $card = $this->db->get('credit_cards', ['name'], ['id' => $expense['credit_card_id']]);
                    if ($card) {
                        $expense['credit_card_name'] = $card['name'];
                    }
                }
                
                if (!empty($expense['bank_account_id'])) {
                    $account = $this->db->get('bank_accounts', ['name'], ['id' => $expense['bank_account_id']]);
                    if ($account) {
                        $expense['bank_account_name'] = $account['name'];
                    }
                }
                
                if (!empty($expense['crypto_wallet_id'])) {
                    $wallet = $this->db->get('crypto_wallets', ['name'], ['id' => $expense['crypto_wallet_id']]);
                    if ($wallet) {
                        $expense['crypto_wallet_name'] = $wallet['name'];
                    }
                }
                
                // Get tags
                $expense['tags'] = $this->tagModel->getTagsForExpense($expense['id']);
            }

            return $expenses;
        }, []);
    }

    /**
     * Count all expenses with filters (for centralized system)
     */
    public function countAllExpensesWithFilters($filters = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters) {
            $conditions = [];
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $conditions['category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method'])) {
                $conditions['payment_method_type'] = $filters['payment_method'];
            }
            
            if (!empty($filters['payment_id'])) {
                switch ($filters['payment_method']) {
                    case 'credit_card':
                        $conditions['credit_card_id'] = $filters['payment_id'];
                        break;
                    case 'bank_account':
                        $conditions['bank_account_id'] = $filters['payment_id'];
                        break;
                    case 'crypto_wallet':
                        $conditions['crypto_wallet_id'] = $filters['payment_id'];
                        break;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['expense_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['expense_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['amount[<=]'] = $filters['amount_max'];
            }
            
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'title[~]' => $filters['search'],
                    'description[~]' => $filters['search'],
                    'vendor[~]' => $filters['search'],
                    'receipt_number[~]' => $filters['search']
                ];
            }
            
            return $this->db->count('expenses', $conditions);
        }, 0);
    }

    /**
     * Get expense statistics for all expenses (for centralized system)
     */
    public function getAllExpenseStats($filters = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filters) {
            $conditions = [];
            
            // Apply filters
            if (!empty($filters['category_id'])) {
                $conditions['category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $conditions['status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_method'])) {
                $conditions['payment_method_type'] = $filters['payment_method'];
            }
            
            if (!empty($filters['payment_id'])) {
                switch ($filters['payment_method']) {
                    case 'credit_card':
                        $conditions['credit_card_id'] = $filters['payment_id'];
                        break;
                    case 'bank_account':
                        $conditions['bank_account_id'] = $filters['payment_id'];
                        break;
                    case 'crypto_wallet':
                        $conditions['crypto_wallet_id'] = $filters['payment_id'];
                        break;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $conditions['expense_date[>=]'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions['expense_date[<=]'] = $filters['date_to'];
            }
            
            if (!empty($filters['amount_min'])) {
                $conditions['amount[>=]'] = $filters['amount_min'];
            }
            
            if (!empty($filters['amount_max'])) {
                $conditions['amount[<=]'] = $filters['amount_max'];
            }
            
            if (!empty($filters['search'])) {
                $conditions['OR'] = [
                    'title[~]' => $filters['search'],
                    'description[~]' => $filters['search'],
                    'vendor[~]' => $filters['search'],
                    'receipt_number[~]' => $filters['search']
                ];
            }

            $total = $this->db->count('expenses', $conditions);
            
            $pendingConditions = array_merge($conditions, ['status' => 'pending']);
            $pending = $this->db->count('expenses', $pendingConditions);
            
            $approvedConditions = array_merge($conditions, ['status' => 'approved']);
            $approved = $this->db->count('expenses', $approvedConditions);
            
            $paidConditions = array_merge($conditions, ['status' => 'paid']);
            $paid = $this->db->count('expenses', $paidConditions);

            $totalAmount = $this->db->sum('expenses', 'amount', $conditions) ?: 0;
            $totalTax = $this->db->sum('expenses', 'tax_amount', $conditions) ?: 0;

            return [
                'total_expenses' => $total,
                'pending_expenses' => $pending,
                'approved_expenses' => $approved,
                'paid_expenses' => $paid,
                'total_amount' => floatval($totalAmount),
                'total_tax' => floatval($totalTax),
                'total_with_tax' => floatval($totalAmount + $totalTax)
            ];
        }, [
            'total_expenses' => 0,
            'pending_expenses' => 0,
            'approved_expenses' => 0,
            'paid_expenses' => 0,
            'total_amount' => 0,
            'total_tax' => 0,
            'total_with_tax' => 0
        ]);
    }

    /**
     * Import expenses from Excel file
     */
    public function importFromExcel($filePath, $userId, $mappings = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($filePath, $userId, $mappings) {
            // Use PhpSpreadsheet to read Excel files properly
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
                
                if (empty($rows)) {
                    return [
                        'success' => false,
                        'error' => 'Excel file is empty',
                        'imported_count' => 0,
                        'skipped_count' => 0
                    ];
                }
                
                $imported = 0;
                $skipped = 0;
                $errors = [];
                
                // Get mapping from form
                $titleCol = $this->getColumnIndex($mappings['map_title'] ?? 'A');
                $amountCol = $this->getColumnIndex($mappings['map_amount'] ?? 'B');
                $categoryCol = $this->getColumnIndex($mappings['map_category'] ?? 'C');
                $dateCol = $this->getColumnIndex($mappings['map_expense_date'] ?? 'D');
                $descCol = $this->getColumnIndex($mappings['map_description'] ?? '');
                $currencyCol = $this->getColumnIndex($mappings['map_currency'] ?? '');
                $taxRateCol = $this->getColumnIndex($mappings['map_tax_rate'] ?? '');
                $notesCol = $this->getColumnIndex($mappings['map_notes'] ?? '');
                $tagsCol = $this->getColumnIndex($mappings['map_tags'] ?? '');
                
                // Get default values from form
                $defaultPaymentMethod = $mappings['default_payment_method'] ?? '';
                $defaultPaymentId = $mappings['default_payment_id'] ?? '';
                $defaultCurrency = $mappings['default_currency'] ?? 'USD';
                $defaultStatus = $mappings['default_status'] ?? 'pending';
                $skipHeader = !empty($mappings['skip_header']);
                $createCategories = !empty($mappings['create_categories']);
                $skipDuplicates = !empty($mappings['skip_duplicates']);
                
                // Get existing expense titles for duplicate checking
                $existingExpenses = $this->db->select('expenses', ['title'], []);
                $existingTitles = array_map('strtolower', array_column($existingExpenses, 'title'));
                
                $processedTitles = []; // Track titles within this import
                $startRow = $skipHeader ? 1 : 0; // Skip header if option is set
                
                for ($i = $startRow; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        $skipped++;
                        continue;
                    }
                    
                    try {
                        // Extract data from mapped columns
                        $title = trim($row[$titleCol] ?? '');
                        $amount = floatval($row[$amountCol] ?? 0);
                        $categoryName = trim($row[$categoryCol] ?? '');
                        $date = $this->parseDate($row[$dateCol] ?? '');
                        $description = trim($descCol >= 0 ? ($row[$descCol] ?? '') : '');
                        $currency = trim($currencyCol >= 0 ? ($row[$currencyCol] ?? $defaultCurrency) : $defaultCurrency);
                        $taxRate = floatval($taxRateCol >= 0 ? ($row[$taxRateCol] ?? 0) : 0);
                        $notes = trim($notesCol >= 0 ? ($row[$notesCol] ?? '') : '');
                        $tagsString = trim($tagsCol >= 0 ? ($row[$tagsCol] ?? '') : '');
                        
                        // Validate required fields
                        if (empty($title) || $amount <= 0) {
                            $skipped++;
                            $errors[] = "Row " . ($i + 1) . ": Title and amount are required";
                            continue;
                        }
                        
                        // Handle category
                        $categoryId = null;
                        if (!empty($categoryName)) {
                            $categoryId = $this->findOrCreateCategory($categoryName, $userId, $createCategories);
                        }
                        
                        // Handle tags (comma-separated)
                        $tagIds = [];
                        if (!empty($tagsString)) {
                            $tagNames = array_map('trim', explode(',', $tagsString));
                            foreach ($tagNames as $tagName) {
                                if (!empty($tagName)) {
                                    $tagId = $this->findOrCreateTag($tagName, $userId, $createCategories);
                                    if ($tagId) {
                                        $tagIds[] = $tagId;
                                    }
                                }
                            }
                        }
                        
                        // Calculate tax amount
                        $taxAmount = ($taxRate > 0) ? ($amount * $taxRate / 100) : 0;
                        
                        // Build expense data
                        $expenseData = [
                            'user_id' => $userId,
                            'title' => $title,
                            'description' => $description,
                            'amount' => $amount,
                            'currency' => $currency,
                            'category_id' => $categoryId,
                            'expense_date' => $date,
                            'tax_rate' => $taxRate,
                            'tax_amount' => $taxAmount,
                            'notes' => $notes,
                            'status' => $defaultStatus,
                            'payment_method_type' => $defaultPaymentMethod
                        ];
                        
                        // Set payment method IDs with validation
                        $expenseData['credit_card_id'] = null;
                        $expenseData['bank_account_id'] = null;
                        $expenseData['crypto_wallet_id'] = null;
                        
                        // Validate payment method exists before setting
                        if (!empty($defaultPaymentMethod) && !empty($defaultPaymentId)) {
                            $paymentExists = false;
                            switch ($defaultPaymentMethod) {
                                case 'credit_card':
                                    $paymentExists = $this->db->has('credit_cards', ['id' => $defaultPaymentId]);
                                    if ($paymentExists) {
                                        $expenseData['credit_card_id'] = $defaultPaymentId;
                                    }
                                    break;
                                case 'bank_account':
                                    $paymentExists = $this->db->has('bank_accounts', ['id' => $defaultPaymentId]);
                                    if ($paymentExists) {
                                        $expenseData['bank_account_id'] = $defaultPaymentId;
                                    }
                                    break;
                                case 'crypto_wallet':
                                    $paymentExists = $this->db->has('crypto_wallets', ['id' => $defaultPaymentId]);
                                    if ($paymentExists) {
                                        $expenseData['crypto_wallet_id'] = $defaultPaymentId;
                                    }
                                    break;
                            }
                            
                            if (!$paymentExists) {
                                $errors[] = "Row " . ($i + 1) . ": Invalid payment method ID '$defaultPaymentId' for method '$defaultPaymentMethod'";
                                // Don't fail the import, just set payment method to null
                                $expenseData['payment_method_type'] = null;
                            }
                        }
                        
                        // Check for duplicate titles
                        if ($skipDuplicates) {
                            $titleLower = strtolower($title);
                            if (in_array($titleLower, $existingTitles) || in_array($titleLower, $processedTitles)) {
                                $skipped++;
                                $errors[] = "Row " . ($i + 1) . ": Duplicate expense title '$title'";
                                continue;
                            }
                            $processedTitles[] = $titleLower;
                        }
                        
                        $expenseId = $this->create($expenseData);
                        if ($expenseId) {
                            // Add tags if any were found/created
                            if (!empty($tagIds)) {
                                $this->addTags($expenseId, $tagIds);
                            }
                            $imported++;
                        } else {
                            $skipped++;
                            $errors[] = "Row " . ($i + 1) . ": Failed to create expense";
                        }
                        
                    } catch (Exception $e) {
                        $skipped++;
                        $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
                    }
                }
                
                return [
                    'success' => true,
                    'imported_count' => $imported,
                    'skipped_count' => $skipped,
                    'errors' => $errors
                ];
                
            } catch (Exception $e) {
                AppLogger::error('Excel import error', [
                    'error' => $e->getMessage(),
                    'file' => $filePath,
                    'user_id' => $userId
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to read Excel file: ' . $e->getMessage(),
                    'imported_count' => 0,
                    'skipped_count' => 0
                ];
            }
            
        }, [
            'success' => false,
            'error' => 'Import operation failed',
            'imported_count' => 0,
            'skipped_count' => 0
        ]);
    }
    
    /**
     * Convert column letter to index (A=0, B=1, etc.)
     */
    private function getColumnIndex($column) {
        if (empty($column)) {
            return -1; // Invalid column
        }
        
        // If it's already a number, convert to index
        if (is_numeric($column)) {
            return intval($column) - 1;
        }
        
        // Convert letter to index (A=0, B=1, etc.)
        $column = strtoupper(trim($column));
        $index = 0;
        $length = strlen($column);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $index - 1;
    }
    
    /**
     * Find existing category or create new one if allowed
     */
    private function findOrCreateCategory($categoryName, $userId, $createCategories) {
        if (empty($categoryName)) {
            return null;
        }
        
        // Try to find existing category by name (case-insensitive, any user for centralized system)
        $category = $this->db->get('categories', ['id'], [
            'name[~]' => $categoryName,
            'LIMIT' => 1
        ]);
        
        if ($category) {
            return $category['id'];
        }
        
        // Create new category if allowed
        if ($createCategories) {
            // Generate a nice color based on category name
            $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'];
            $colorIndex = crc32(strtolower($categoryName)) % count($colors);
            
            // Generate appropriate icon based on category name
            $iconMap = [
                'office' => 'fas fa-building',
                'travel' => 'fas fa-plane',
                'meals' => 'fas fa-utensils',
                'food' => 'fas fa-utensils',
                'transport' => 'fas fa-car',
                'software' => 'fas fa-laptop-code',
                'training' => 'fas fa-graduation-cap',
                'equipment' => 'fas fa-tools',
                'supplies' => 'fas fa-box',
                'entertainment' => 'fas fa-film',
                'marketing' => 'fas fa-bullhorn',
                'insurance' => 'fas fa-shield-alt',
                'rent' => 'fas fa-home',
                'utilities' => 'fas fa-bolt',
                'phone' => 'fas fa-phone',
                'internet' => 'fas fa-wifi',
                'legal' => 'fas fa-gavel',
                'accounting' => 'fas fa-calculator',
                'medical' => 'fas fa-heartbeat',
                'fuel' => 'fas fa-gas-pump',
                'parking' => 'fas fa-parking'
            ];
            
            $icon = 'fas fa-folder'; // default
            foreach ($iconMap as $keyword => $iconClass) {
                if (stripos($categoryName, $keyword) !== false) {
                    $icon = $iconClass;
                    break;
                }
            }
            
            $categoryData = [
                'user_id' => $userId,
                'name' => $categoryName,
                'description' => "Auto-created during import for: $categoryName",
                'color' => $colors[$colorIndex],
                'icon' => $icon
            ];
            
            $result = $this->db->insert('categories', $categoryData);
            if ($result) {
                return $this->db->id();
            }
        }
        
        return null;
    }

    /**
     * Export expenses to Excel file (CSV format)
     */
    public function exportToExcel($userId = null, $filters = []) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $filters) {
            // Get expenses based on user or filters
            if ($userId) {
                $expenses = $this->getExpensesWithFilters($userId, $filters, 1, 10000);
            } else {
                $expenses = $this->getAllExpensesWithFilters($filters, 1, 10000);
            }
            
            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'expenses_export_');
            $csvFile = $tempFile . '.csv';
            rename($tempFile, $csvFile);
            
            if (($handle = fopen($csvFile, 'w')) !== FALSE) {
                // Write header
                $header = [
                    'Title',
                    'Description', 
                    'Amount',
                    'Currency',
                    'Expense Date',
                    'Status',
                    'Payment Method',
                    'Tax Rate',
                    'Tax Amount',
                    'Notes',
                    'Vendor',
                    'Receipt Number',
                    'Category',
                    'Creator',
                    'Created Date'
                ];
                fputcsv($handle, $header);
                
                // Write data
                foreach ($expenses as $expense) {
                    $row = [
                        $expense['title'],
                        $expense['description'],
                        $expense['amount'],
                        $expense['currency'],
                        $expense['expense_date'],
                        $expense['status'],
                        $expense['payment_method_type'],
                        $expense['tax_rate'],
                        $expense['tax_amount'],
                        $expense['notes'],
                        $expense['vendor'],
                        $expense['receipt_number'],
                        $expense['category_name'] ?? '',
                        $expense['creator_name'] ?? '',
                        $expense['created_at']
                    ];
                    fputcsv($handle, $row);
                }
                
                fclose($handle);
                return $csvFile;
            }
            
            return false;
            
        }, false);
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString) {
        if (empty($dateString)) {
            return date('Y-m-d');
        }
        
        // Try to parse common date formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // If all else fails, try strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // Default to today
        return date('Y-m-d');
    }

    /**
     * Find existing tag or create new one if allowed
     */
    private function findOrCreateTag($tagName, $userId, $createTags) {
        if (empty($tagName)) {
            return null;
        }
        
        // Try to find existing tag by name (case-insensitive, any user for centralized system)
        $tag = $this->db->get('tags', ['id'], [
            'name[~]' => $tagName,
            'LIMIT' => 1
        ]);
        
        if ($tag) {
            return $tag['id'];
        }
        
        // Create new tag if allowed
        if ($createTags) {
            // Generate a nice color based on tag name
            $colors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'];
            $colorIndex = crc32(strtolower($tagName)) % count($colors);
            
            $tagData = [
                'user_id' => $userId,
                'name' => $tagName,
                'description' => "Auto-created during import for: $tagName",
                'color' => $colors[$colorIndex]
            ];
            
            $result = $this->db->insert('tags', $tagData);
            if ($result) {
                return $this->db->id();
            }
        }
        
        return null;
    }
} 