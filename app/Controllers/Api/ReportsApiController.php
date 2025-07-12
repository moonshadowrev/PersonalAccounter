<?php

require_once __DIR__ . '/ApiController.php';

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Report and analytics operations"
 * )
 */
class ReportsApiController extends ApiController {
    
    public function __construct($database) {
        parent::__construct($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/reports/dashboard",
     *     summary="Get dashboard statistics",
     *     tags={"Reports"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_expenses", type="number", format="float", example=1250.50),
     *                 @OA\Property(property="total_subscriptions", type="integer", example=15),
     *                 @OA\Property(property="active_subscriptions", type="integer", example=12),
     *                 @OA\Property(property="total_monthly_cost", type="number", format="float", example=89.99),
     *                 @OA\Property(property="total_annual_cost", type="number", format="float", example=1079.88),
     *                 @OA\Property(property="expense_count", type="integer", example=42),
     *                 @OA\Property(property="categories_count", type="integer", example=8),
     *                 @OA\Property(property="tags_count", type="integer", example=15)
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function dashboard() {
        $this->logRequest('Get Dashboard Statistics');
        
        if (!$this->hasPermission('reports.read')) {
            $this->forbidden('Permission denied: reports.read required');
        }
        
        $fromDate = $this->request['query']['from_date'] ?? null;
        $toDate = $this->request['query']['to_date'] ?? null;
        
        try {
            $stats = [];
            
            // Base conditions for date filtering
            $dateConditions = [];
            if ($fromDate) {
                $dateConditions['created_at[>=]'] = $fromDate . ' 00:00:00';
            }
            if ($toDate) {
                $dateConditions['created_at[<=]'] = $toDate . ' 23:59:59';
            }
            
            // Total expenses
            $expenseConditions = $dateConditions;
            $stats['total_expenses'] = (float) $this->db->sum('expenses', 'amount', $expenseConditions);
            $stats['expense_count'] = $this->db->count('expenses', $expenseConditions);
            
            // Subscription statistics
            $subscriptionConditions = $dateConditions;
            $stats['total_subscriptions'] = $this->db->count('subscriptions', $subscriptionConditions);
            $stats['active_subscriptions'] = $this->db->count('subscriptions', array_merge($subscriptionConditions, ['status' => 'active']));
            $stats['expired_subscriptions'] = $this->db->count('subscriptions', array_merge($subscriptionConditions, ['status' => 'expired']));
            $stats['cancelled_subscriptions'] = $this->db->count('subscriptions', array_merge($subscriptionConditions, ['status' => 'cancelled']));
            
            // Monthly and annual costs
            $activeSubscriptions = $this->db->select('subscriptions', ['amount', 'billing_cycle'], array_merge($subscriptionConditions, ['status' => 'active']));
            
            $monthlyTotal = 0;
            $annualTotal = 0;
            foreach ($activeSubscriptions as $subscription) {
                $amount = (float) $subscription['amount'];
                switch ($subscription['billing_cycle']) {
                    case 'monthly':
                        $monthlyTotal += $amount;
                        $annualTotal += $amount * 12;
                        break;
                    case 'annual':
                        $monthlyTotal += $amount / 12;
                        $annualTotal += $amount;
                        break;
                    case 'weekly':
                        $monthlyTotal += $amount * 4.33;
                        $annualTotal += $amount * 52;
                        break;
                    case 'daily':
                        $monthlyTotal += $amount * 30;
                        $annualTotal += $amount * 365;
                        break;
                    case 'onetime':
                        // One-time payments don't contribute to recurring costs
                        break;
                }
            }
            
            $stats['total_monthly_cost'] = round($monthlyTotal, 2);
            $stats['total_annual_cost'] = round($annualTotal, 2);
            $stats['avg_monthly_spend'] = $stats['active_subscriptions'] > 0 ? round($monthlyTotal / $stats['active_subscriptions'], 2) : 0;
            
            // One-time costs
            $onetimeSubscriptions = $this->db->select('subscriptions', ['amount'], array_merge($subscriptionConditions, ['billing_cycle' => 'onetime']));
            $stats['total_onetime_cost'] = (float) array_sum(array_column($onetimeSubscriptions, 'amount'));
            
            // Categories and tags count
            $stats['categories_count'] = $this->db->count('categories');
            $stats['tags_count'] = $this->db->count('tags');
            
            // Bank accounts and crypto wallets
            $stats['bank_accounts_count'] = $this->db->count('bank_accounts', ['is_active' => true]);
            $stats['crypto_wallets_count'] = $this->db->count('crypto_wallets', ['is_active' => true]);
            $stats['credit_cards_count'] = $this->db->count('credit_cards', ['is_active' => true]);
            
            $this->success($stats, 'Dashboard statistics retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Dashboard Statistics', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve dashboard statistics');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/reports/expenses",
     *     summary="Get expense analytics",
     *     tags={"Reports"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="group_by",
     *         in="query",
     *         description="Group expenses by period",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year"}, default="month")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="by_category", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="by_period", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="by_payment_method", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="summary", type="object")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function expenses() {
        $this->logRequest('Get Expense Analytics');
        
        if (!$this->hasPermission('reports.read')) {
            $this->forbidden('Permission denied: reports.read required');
        }
        
        $fromDate = $this->request['query']['from_date'] ?? null;
        $toDate = $this->request['query']['to_date'] ?? null;
        $groupBy = $this->request['query']['group_by'] ?? 'month';
        
        try {
            $dateConditions = [];
            if ($fromDate) {
                $dateConditions['expenses.created_at[>=]'] = $fromDate . ' 00:00:00';
            }
            if ($toDate) {
                $dateConditions['expenses.created_at[<=]'] = $toDate . ' 23:59:59';
            }
            
            $analytics = [];
            
            // Expenses by category
            $categoryExpenses = $this->db->select('expenses', [
                '[>]categories' => ['category_id' => 'id']
            ], [
                'categories.name(category_name)',
                'categories.color(category_color)',
                'expenses.amount'
            ], $dateConditions);
            
            $byCategory = [];
            foreach ($categoryExpenses as $expense) {
                $categoryName = $expense['category_name'] ?? 'Uncategorized';
                if (!isset($byCategory[$categoryName])) {
                    $byCategory[$categoryName] = [
                        'name' => $categoryName,
                        'color' => $expense['category_color'] ?? '#6B7280',
                        'total' => 0,
                        'count' => 0
                    ];
                }
                $byCategory[$categoryName]['total'] += (float) $expense['amount'];
                $byCategory[$categoryName]['count']++;
            }
            $analytics['by_category'] = array_values($byCategory);
            
            // Expenses by period
            $periodFormat = match($groupBy) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m'
            };
            
            $periodExpenses = $this->db->query("
                SELECT 
                    DATE_FORMAT(created_at, '{$periodFormat}') as period,
                    SUM(amount) as total,
                    COUNT(*) as count
                FROM expenses 
                WHERE 1=1 
                " . ($fromDate ? "AND created_at >= '{$fromDate} 00:00:00'" : "") . "
                " . ($toDate ? "AND created_at <= '{$toDate} 23:59:59'" : "") . "
                GROUP BY period 
                ORDER BY period
            ")->fetchAll();
            
            $analytics['by_period'] = $periodExpenses;
            
            // Expenses by payment method
            $paymentMethods = [
                'credit_card' => 'Credit Card',
                'bank_account' => 'Bank Account', 
                'crypto_wallet' => 'Crypto Wallet',
                'cash' => 'Cash'
            ];
            
            $byPaymentMethod = [];
            foreach ($paymentMethods as $method => $label) {
                $total = (float) $this->db->sum('expenses', 'amount', array_merge($dateConditions, [
                    'payment_method' => $method
                ]));
                $count = $this->db->count('expenses', array_merge($dateConditions, [
                    'payment_method' => $method
                ]));
                
                if ($total > 0) {
                    $byPaymentMethod[] = [
                        'method' => $method,
                        'label' => $label,
                        'total' => $total,
                        'count' => $count
                    ];
                }
            }
            $analytics['by_payment_method'] = $byPaymentMethod;
            
            // Summary
            $analytics['summary'] = [
                'total_amount' => (float) $this->db->sum('expenses', 'amount', $dateConditions),
                'total_count' => $this->db->count('expenses', $dateConditions),
                'average_amount' => 0,
                'date_range' => [
                    'from' => $fromDate,
                    'to' => $toDate
                ]
            ];
            
            if ($analytics['summary']['total_count'] > 0) {
                $analytics['summary']['average_amount'] = round($analytics['summary']['total_amount'] / $analytics['summary']['total_count'], 2);
            }
            
            $this->success($analytics, 'Expense analytics retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Expense Analytics', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve expense analytics');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/reports/subscriptions",
     *     summary="Get subscription analytics",
     *     tags={"Reports"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subscription analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="by_status", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="by_billing_cycle", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="by_currency", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="top_services", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="summary", type="object")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function subscriptions() {
        $this->logRequest('Get Subscription Analytics');
        
        if (!$this->hasPermission('reports.read')) {
            $this->forbidden('Permission denied: reports.read required');
        }
        
        try {
            $analytics = [];
            
            // By status
            $statuses = ['active', 'expired', 'cancelled', 'paused'];
            $byStatus = [];
            foreach ($statuses as $status) {
                $count = $this->db->count('subscriptions', ['status' => $status]);
                $total = (float) $this->db->sum('subscriptions', 'amount', ['status' => $status]);
                
                $byStatus[] = [
                    'status' => $status,
                    'count' => $count,
                    'total_amount' => $total
                ];
            }
            $analytics['by_status'] = $byStatus;
            
            // By billing cycle
            $cycles = ['monthly', 'annual', 'weekly', 'daily', 'onetime'];
            $byBillingCycle = [];
            foreach ($cycles as $cycle) {
                $count = $this->db->count('subscriptions', ['billing_cycle' => $cycle]);
                $total = (float) $this->db->sum('subscriptions', 'amount', ['billing_cycle' => $cycle]);
                
                if ($count > 0) {
                    $byBillingCycle[] = [
                        'cycle' => $cycle,
                        'count' => $count,
                        'total_amount' => $total
                    ];
                }
            }
            $analytics['by_billing_cycle'] = $byBillingCycle;
            
            // By currency
            $currencies = $this->db->select('subscriptions', 'currency', [
                'GROUP' => 'currency'
            ]);
            
            $byCurrency = [];
            foreach ($currencies as $currency) {
                $count = $this->db->count('subscriptions', ['currency' => $currency]);
                $total = (float) $this->db->sum('subscriptions', 'amount', ['currency' => $currency]);
                
                $byCurrency[] = [
                    'currency' => $currency,
                    'count' => $count,
                    'total_amount' => $total
                ];
            }
            $analytics['by_currency'] = $byCurrency;
            
            // Top services by amount
            $topServices = $this->db->select('subscriptions', [
                'plan_name',
                'amount',
                'billing_cycle',
                'currency'
            ], [
                'ORDER' => ['amount' => 'DESC'],
                'LIMIT' => 10
            ]);
            
            $analytics['top_services'] = $topServices;
            
            // Summary
            $analytics['summary'] = [
                'total_subscriptions' => $this->db->count('subscriptions'),
                'active_subscriptions' => $this->db->count('subscriptions', ['status' => 'active']),
                'total_monthly_value' => 0,
                'total_annual_value' => 0
            ];
            
            // Calculate monthly and annual values
            $activeSubscriptions = $this->db->select('subscriptions', ['amount', 'billing_cycle'], ['status' => 'active']);
            $monthlyValue = 0;
            $annualValue = 0;
            
            foreach ($activeSubscriptions as $subscription) {
                $amount = (float) $subscription['amount'];
                switch ($subscription['billing_cycle']) {
                    case 'monthly':
                        $monthlyValue += $amount;
                        $annualValue += $amount * 12;
                        break;
                    case 'annual':
                        $monthlyValue += $amount / 12;
                        $annualValue += $amount;
                        break;
                    case 'weekly':
                        $monthlyValue += $amount * 4.33;
                        $annualValue += $amount * 52;
                        break;
                    case 'daily':
                        $monthlyValue += $amount * 30;
                        $annualValue += $amount * 365;
                        break;
                }
            }
            
            $analytics['summary']['total_monthly_value'] = round($monthlyValue, 2);
            $analytics['summary']['total_annual_value'] = round($annualValue, 2);
            
            $this->success($analytics, 'Subscription analytics retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Subscription Analytics', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve subscription analytics');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/reports/export",
     *     summary="Export data to various formats",
     *     tags={"Reports"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type of data to export",
     *         required=true,
     *         @OA\Schema(type="string", enum={"expenses", "subscriptions", "categories", "tags"})
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Export format",
     *         required=false,
     *         @OA\Schema(type="string", enum={"csv", "json", "xlsx"}, default="csv")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Start date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="End date for filtering (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Export data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Export data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="download_url", type="string", example="/api/v1/reports/download/export_123456.csv"),
     *                 @OA\Property(property="filename", type="string", example="expenses_export_2024-01-15.csv"),
     *                 @OA\Property(property="record_count", type="integer", example=125),
     *                 @OA\Property(property="format", type="string", example="csv")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function export() {
        $this->logRequest('Export Data');
        
        if (!$this->hasPermission('reports.export')) {
            $this->forbidden('Permission denied: reports.export required');
        }
        
        $type = $this->request['query']['type'] ?? null;
        $format = $this->request['query']['format'] ?? 'csv';
        $fromDate = $this->request['query']['from_date'] ?? null;
        $toDate = $this->request['query']['to_date'] ?? null;
        
        // Validate required parameters
        if (!$type) {
            $this->validationError(['type' => 'The type parameter is required.']);
        }
        
        $validTypes = ['expenses', 'subscriptions', 'categories', 'tags'];
        if (!in_array($type, $validTypes)) {
            $this->validationError(['type' => 'Type must be one of: ' . implode(', ', $validTypes)]);
        }
        
        $validFormats = ['csv', 'json', 'xlsx'];
        if (!in_array($format, $validFormats)) {
            $this->validationError(['format' => 'Format must be one of: ' . implode(', ', $validFormats)]);
        }
        
        try {
            $data = [];
            $dateConditions = [];
            
            if ($fromDate) {
                $dateConditions['created_at[>=]'] = $fromDate . ' 00:00:00';
            }
            if ($toDate) {
                $dateConditions['created_at[<=]'] = $toDate . ' 23:59:59';
            }
            
            // Get data based on type
            switch ($type) {
                case 'expenses':
                    $data = $this->db->select('expenses', [
                        '[>]categories' => ['category_id' => 'id'],
                        '[>]users' => ['user_id' => 'id']
                    ], [
                        'expenses.id',
                        'expenses.amount',
                        'expenses.description',
                        'expenses.date',
                        'expenses.payment_method',
                        'expenses.status',
                        'categories.name(category_name)',
                        'users.name(user_name)',
                        'expenses.created_at'
                    ], $dateConditions);
                    break;
                    
                case 'subscriptions':
                    $data = $this->db->select('subscriptions', [
                        '[>]users' => ['user_id' => 'id']
                    ], [
                        'subscriptions.id',
                        'subscriptions.plan_name',
                        'subscriptions.amount',
                        'subscriptions.currency',
                        'subscriptions.billing_cycle',
                        'subscriptions.status',
                        'subscriptions.start_date',
                        'subscriptions.next_billing_date',
                        'users.name(user_name)',
                        'subscriptions.created_at'
                    ], $dateConditions);
                    break;
                    
                case 'categories':
                    $data = $this->db->select('categories', [
                        '[>]users' => ['user_id' => 'id']
                    ], [
                        'categories.id',
                        'categories.name',
                        'categories.description',
                        'categories.color',
                        'categories.icon',
                        'users.name(user_name)',
                        'categories.created_at'
                    ], $dateConditions);
                    break;
                    
                case 'tags':
                    $data = $this->db->select('tags', [
                        '[>]users' => ['user_id' => 'id']
                    ], [
                        'tags.id',
                        'tags.name',
                        'tags.description',
                        'tags.color',
                        'users.name(user_name)',
                        'tags.created_at'
                    ], $dateConditions);
                    break;
            }
            
            $filename = $type . '_export_' . date('Y-m-d_H-i-s') . '.' . $format;
            $recordCount = count($data);
            
            // For API, we'll return the data directly in the requested format
            $response = [
                'data' => $data,
                'filename' => $filename,
                'record_count' => $recordCount,
                'format' => $format,
                'export_info' => [
                    'type' => $type,
                    'date_range' => [
                        'from' => $fromDate,
                        'to' => $toDate
                    ],
                    'generated_at' => date('c')
                ]
            ];
            
            $this->success($response, 'Export data retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Export Data', ['type' => $type, 'format' => $format, 'error' => $e->getMessage()]);
            $this->serverError('Failed to export data');
        }
    }
} 