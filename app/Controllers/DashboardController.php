<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Subscription.php';
require_once __DIR__ . '/../Models/CreditCard.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/Expense.php';
require_once __DIR__ . '/../Services/TransactionService.php';

class DashboardController extends Controller {

    protected $db;
    private $subscriptionModel;
    private $creditCardModel;
    private $transactionModel;
    private $expenseModel;
    private $transactionService;

    public function __construct($db) {
        $this->db = $db;
        $this->subscriptionModel = new Subscription($db);
        $this->creditCardModel = new CreditCard($db);
        $this->transactionModel = new Transaction($db);
        $this->expenseModel = new Expense($db);
        $this->transactionService = new TransactionService($db);
    }

    public function index() {
        // Wrap the entire dashboard operation in error handling
        return ErrorHandler::wrap(function() {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                // Redirect to login if no user session
                header('Location: /login');
                exit();
            }

            // Date filter - default to show all data (no date filtering for initial load)
            $from_date = $_GET['from'] ?? null;
            $to_date = $_GET['to'] ?? null;
            
            // Only apply date filtering if both dates are provided
            $applyDateFilter = $from_date && $to_date;
            
            if ($applyDateFilter) {
                // Validate dates
                $from_date = $this->validateDate($from_date) ? $from_date : null;
                $to_date = $this->validateDate($to_date) ? $to_date : null;
                $applyDateFilter = $from_date && $to_date;
            }

            // Get optimized data - show all data by default, filter only when requested
            if ($applyDateFilter) {
                $stats = $this->getOptimizedStats($userId, $from_date, $to_date);
                $analyticsData = $this->getOptimizedAnalytics($userId, $from_date, $to_date);
                $expenseStats = $this->getOptimizedExpenseStats($from_date, $to_date);
                $expenseAnalytics = $this->getOptimizedExpenseAnalytics($from_date, $to_date);
                $subscriptions = $this->subscriptionModel->getAllWithDateFilter($from_date, $to_date);
                $transactionStats = $this->transactionService->getTransactionStats($from_date, $to_date);
                $transactionAnalytics = $this->getTransactionAnalytics($from_date, $to_date);
            } else {
                // Show all data without date filtering
                $stats = $this->getAllStats($userId);
                $analyticsData = $this->getAllAnalytics($userId);
                $expenseStats = $this->getAllExpenseStats();
                $expenseAnalytics = $this->getAllExpenseAnalytics();
                $subscriptions = $this->subscriptionModel->getAllWithUserInfo();
                $transactionStats = $this->transactionService->getTransactionStats();
                $transactionAnalytics = $this->getTransactionAnalytics();
                $from_date = '';
                $to_date = '';
            }
            
            $creditCards = $this->creditCardModel->getAll('*', ['ORDER' => ['created_at' => 'DESC']]);
            
            $this->view('dashboard/index', [
                'stats' => $stats,
                'expense_stats' => $expenseStats,
                'filter_dates' => ['from' => $from_date, 'to' => $to_date],
                'subscriptions' => $subscriptions,
                'credit_cards' => $creditCards,
                'analytics' => $analyticsData,
                'expense_analytics' => $expenseAnalytics,
                'transaction_stats' => $transactionStats,
                'transaction_analytics' => $transactionAnalytics
            ]);
        });
    }
    
    /**
     * Get all statistics without date filtering
     */
    private function getAllStats($userId) {
        try {
            // Get basic counts without date filtering
            $totalSubscriptions = $this->db->count("subscriptions");
            $activeSubscriptions = $this->db->count("subscriptions", ["status" => "active"]);
            $expiredSubscriptions = $this->db->count("subscriptions", ["status" => "expired"]);
            $cancelledSubscriptions = $this->db->count("subscriptions", ["status" => "cancelled"]);
            
            // Get all subscriptions for amount calculations
            $subscriptions = $this->db->select("subscriptions", ["amount", "billing_cycle", "status"]);
            
            // Calculate amounts
            $monthlyDirect = 0;
            $yearlyToMonthly = 0;
            $weeklyToMonthly = 0;
            $quarterlyToMonthly = 0;
            $yearlyActual = 0;
            $onetimeTotal = 0;
            
            foreach ($subscriptions as $sub) {
                $amount = floatval($sub['amount'] ?? 0);
                $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                $status = strtolower($sub['status'] ?? 'active');
                
                if ($status === 'active') {
                    switch ($cycle) {
                        case 'monthly':
                            $monthlyDirect += $amount;
                            break;
                        case 'yearly':
                            $yearlyToMonthly += $amount / 12;
                            $yearlyActual += $amount;
                            break;
                        case 'weekly':
                            $weeklyToMonthly += $amount * 4.33;
                            break;
                        case 'quarterly':
                            $quarterlyToMonthly += $amount / 3;
                            break;
                    }
                }
                
                if ($cycle === 'one-time') {
                    $onetimeTotal += $amount;
                }
            }
            
            // Calculate derived values
            $totalMonthlyEquivalent = $monthlyDirect + $yearlyToMonthly + $weeklyToMonthly + $quarterlyToMonthly;
            $avgMonthlySpend = $activeSubscriptions > 0 ? $totalMonthlyEquivalent / $activeSubscriptions : 0;
            $totalAnnualRecurring = $totalMonthlyEquivalent * 12 + $yearlyActual;
            
            return [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'cancelled_subscriptions' => $cancelledSubscriptions,
                'total_monthly_cost' => $totalMonthlyEquivalent,
                'total_yearly_cost' => $yearlyActual,
                'total_onetime_cost' => $onetimeTotal,
                'avg_monthly_spend' => $avgMonthlySpend,
                'total_annual_cost' => $totalAnnualRecurring
            ];
            
        } catch (Exception $e) {
            // Fallback to empty stats if query fails
            return [
                'total_subscriptions' => 0,
                'active_subscriptions' => 0,
                'expired_subscriptions' => 0,
                'cancelled_subscriptions' => 0,
                'total_monthly_cost' => 0,
                'total_yearly_cost' => 0,
                'total_onetime_cost' => 0,
                'avg_monthly_spend' => 0,
                'total_annual_cost' => 0
            ];
        }
    }

    /**
     * Get all analytics without date filtering
     */
    private function getAllAnalytics($userId) {
        try {
            // Get all subscriptions for analytics
            $subscriptions = $this->db->select("subscriptions", "*");
            
            return [
                'status_distribution' => $this->getStatusDistribution($subscriptions),
                'billing_cycle_distribution' => $this->getBillingCycleDistribution($subscriptions),
                'monthly_spending_trend' => $this->getMonthlySpendingTrendAll($subscriptions),
                'top_services' => $this->getTopSpendingServices($subscriptions),
                'currency_breakdown' => $this->getCurrencyBreakdown($subscriptions),
                'subscription_growth' => $this->getSubscriptionGrowthAll($subscriptions),
                'billing_cycle_costs' => $this->getBillingCycleCosts($subscriptions)
            ];
            
        } catch (Exception $e) {
            // Return empty analytics if query fails
            return [
                'status_distribution' => ['active' => 0, 'expired' => 0, 'cancelled' => 0],
                'billing_cycle_distribution' => [],
                'monthly_spending_trend' => ['labels' => [], 'data' => []],
                'top_services' => ['labels' => [], 'data' => []],
                'currency_breakdown' => ['labels' => [], 'data' => []],
                'subscription_growth' => ['labels' => [], 'data' => []],
                'billing_cycle_costs' => []
            ];
        }
    }

    /**
     * Optimized statistics calculation using direct database queries
     */
    private function getOptimizedStats($userId, $from_date, $to_date) {
        try {
            // Use simpler approach with multiple queries to avoid Medoo parameter binding issues
            $whereConditions = [
                "created_at[>=]" => $from_date . ' 00:00:00',
                "created_at[<=]" => $to_date . ' 23:59:59'
            ];
            
            // Get basic counts
            $totalSubscriptions = $this->db->count("subscriptions", $whereConditions);
            
            $activeConditions = array_merge($whereConditions, ["status" => "active"]);
            $activeSubscriptions = $this->db->count("subscriptions", $activeConditions);
            
            $expiredConditions = array_merge($whereConditions, ["status" => "expired"]);
            $expiredSubscriptions = $this->db->count("subscriptions", $expiredConditions);
            
            $cancelledConditions = array_merge($whereConditions, ["status" => "cancelled"]);
            $cancelledSubscriptions = $this->db->count("subscriptions", $cancelledConditions);
            
            // Get all subscriptions for amount calculations
            $subscriptions = $this->db->select("subscriptions", ["amount", "billing_cycle", "status"], $whereConditions);
            
            // Calculate amounts
            $monthlyDirect = 0;
            $yearlyToMonthly = 0;
            $weeklyToMonthly = 0;
            $quarterlyToMonthly = 0;
            $yearlyActual = 0;
            $onetimeTotal = 0;
            
            foreach ($subscriptions as $sub) {
                $amount = floatval($sub['amount'] ?? 0);
                $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                $status = strtolower($sub['status'] ?? 'active');
                
                if ($status === 'active') {
                    switch ($cycle) {
                        case 'monthly':
                            $monthlyDirect += $amount;
                            break;
                        case 'yearly':
                            $yearlyToMonthly += $amount / 12;
                            $yearlyActual += $amount;
                            break;
                        case 'weekly':
                            $weeklyToMonthly += $amount * 4.33;
                            break;
                        case 'quarterly':
                            $quarterlyToMonthly += $amount / 3;
                            break;
                    }
                }
                
                if ($cycle === 'one-time') {
                    $onetimeTotal += $amount;
                }
            }
            
            $stats = [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'cancelled_subscriptions' => $cancelledSubscriptions,
                'monthly_direct' => $monthlyDirect,
                'yearly_to_monthly' => $yearlyToMonthly,
                'weekly_to_monthly' => $weeklyToMonthly,
                'quarterly_to_monthly' => $quarterlyToMonthly,
                'yearly_actual' => $yearlyActual,
                'onetime_total' => $onetimeTotal
            ];
            
            // Calculate derived values
            $totalMonthlyEquivalent = floatval($stats['monthly_direct']) + 
                                    floatval($stats['yearly_to_monthly']) + 
                                    floatval($stats['weekly_to_monthly']) + 
                                    floatval($stats['quarterly_to_monthly']);
            
            $totalYearlyActual = floatval($stats['yearly_actual']);
            $totalOnetimeActual = floatval($stats['onetime_total']);
            $activeCount = intval($stats['active_subscriptions']);
            
            $avgMonthlySpend = $activeCount > 0 ? $totalMonthlyEquivalent / $activeCount : 0;
            $totalAnnualRecurring = $totalMonthlyEquivalent * 12 + $totalYearlyActual;
            
            return [
                'total_subscriptions' => intval($stats['total_subscriptions']),
                'active_subscriptions' => $activeCount,
                'expired_subscriptions' => intval($stats['expired_subscriptions']),
                'cancelled_subscriptions' => intval($stats['cancelled_subscriptions']),
                'total_monthly_cost' => $totalMonthlyEquivalent,
                'total_yearly_cost' => $totalYearlyActual,
                'total_onetime_cost' => $totalOnetimeActual,
                'avg_monthly_spend' => $avgMonthlySpend,
                'total_annual_cost' => $totalAnnualRecurring
            ];
            
        } catch (Exception $e) {
            // Log the error for debugging
            AppLogger::error('Stats calculation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'from_date' => $from_date,
                'to_date' => $to_date
            ]);
            
            // Fallback to empty stats if query fails
            return [
                'total_subscriptions' => 0,
                'active_subscriptions' => 0,
                'expired_subscriptions' => 0,
                'cancelled_subscriptions' => 0,
                'total_monthly_cost' => 0,
                'total_yearly_cost' => 0,
                'total_onetime_cost' => 0,
                'avg_monthly_spend' => 0,
                'total_annual_cost' => 0
            ];
        }
    }
    
    /**
     * Optimized analytics data with date filtering
     */
    private function getOptimizedAnalytics($userId, $from_date, $to_date) {
        try {
            // Get filtered subscriptions for analytics
            $subscriptions = $this->db->select("subscriptions", "*", [
                "created_at[>=]" => $from_date . ' 00:00:00',
                "created_at[<=]" => $to_date . ' 23:59:59'
            ]);
            
            return [
                'status_distribution' => $this->getStatusDistribution($subscriptions),
                'billing_cycle_distribution' => $this->getBillingCycleDistribution($subscriptions),
                'monthly_spending_trend' => $this->getMonthlySpendingTrend($subscriptions, $from_date, $to_date),
                'top_services' => $this->getTopSpendingServices($subscriptions),
                'currency_breakdown' => $this->getCurrencyBreakdown($subscriptions),
                'subscription_growth' => $this->getSubscriptionGrowth($subscriptions, $from_date, $to_date),
                'billing_cycle_costs' => $this->getBillingCycleCosts($subscriptions)
            ];
            
        } catch (Exception $e) {
            // Return empty analytics if query fails
            return [
                'status_distribution' => ['active' => 0, 'expired' => 0, 'cancelled' => 0],
                'billing_cycle_distribution' => [],
                'monthly_spending_trend' => ['labels' => [], 'data' => []],
                'top_services' => ['labels' => [], 'data' => []],
                'currency_breakdown' => ['labels' => [], 'data' => []],
                'subscription_growth' => ['labels' => [], 'data' => []],
                'billing_cycle_costs' => []
            ];
        }
    }
    
    /**
     * Validate date format
     */
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function getStatusDistribution($subscriptions) {
        $distribution = ['active' => 0, 'expired' => 0, 'cancelled' => 0];
        
        foreach ($subscriptions as $sub) {
            $status = strtolower($sub['status'] ?? 'active');
            if (isset($distribution[$status])) {
                $distribution[$status]++;
            }
        }
        
        return $distribution;
    }
    
    private function getBillingCycleDistribution($subscriptions) {
        $distribution = ['monthly' => 0, 'yearly' => 0, 'one-time' => 0];
        
        foreach ($subscriptions as $sub) {
            $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
            if (isset($distribution[$cycle])) {
                $distribution[$cycle]++;
            }
        }
        
        return array_filter($distribution); // Remove zero values
    }
    
    private function getBillingCycleCosts($subscriptions) {
        $costs = ['monthly' => 0, 'yearly' => 0, 'one-time' => 0];
        
        foreach ($subscriptions as $sub) {
            if (strtolower($sub['status'] ?? 'active') === 'active') {
                $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                $amount = floatval($sub['amount'] ?? 0);
                
                if (isset($costs[$cycle])) {
                    $costs[$cycle] += $amount;
                }
            }
        }
        
        return array_filter($costs); // Remove zero values
    }
    
    private function getMonthlySpendingTrend($subscriptions, $from_date, $to_date) {
        $months = [];
        $spending = [];
        
        // Calculate months between from_date and to_date, max 12 months
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $interval = $start->diff($end);
        $monthsCount = min(12, $interval->m + ($interval->y * 12) + 1);
        
        // Generate months within the date range
        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $date = clone $end;
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Calculate monthly equivalent spending for this period
            $monthlyEquivalent = 0;
            foreach ($subscriptions as $sub) {
                $subDate = new DateTime($sub['created_at']);
                if ($subDate <= $date && strtolower($sub['status'] ?? 'active') === 'active') {
                    $amount = floatval($sub['amount'] ?? 0);
                    $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                    
                    // Convert to monthly equivalent for comparison
                    $subMonthlyEquivalent = 0;
                    switch ($cycle) {
                        case 'monthly':
                            $subMonthlyEquivalent = $amount;
                            break;
                        case 'yearly':
                            $subMonthlyEquivalent = $amount / 12;
                            break;
                        case 'one-time':
                            $subMonthlyEquivalent = 0; // Don't include one-time in recurring comparison
                            break;
                    }
                    $monthlyEquivalent += $subMonthlyEquivalent;
                }
            }
            $spending[] = round($monthlyEquivalent, 2);
        }
        
        return ['labels' => $months, 'data' => $spending];
    }
    
    private function getTopSpendingServices($subscriptions) {
        $services = [];
        
        foreach ($subscriptions as $sub) {
            if (strtolower($sub['status'] ?? 'active') === 'active') {
                $name = $sub['name'] ?? 'Unknown Service';
                $amount = floatval($sub['amount'] ?? 0);
                $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                
                // Convert to monthly equivalent for comparison
                $monthlyEquivalent = 0;
                switch ($cycle) {
                    case 'monthly':
                        $monthlyEquivalent = $amount;
                        break;
                    case 'yearly':
                        $monthlyEquivalent = $amount / 12;
                        break;
                    case 'one-time':
                        $monthlyEquivalent = 0; // Don't include one-time in recurring comparison
                        break;
                }
                
                if ($monthlyEquivalent > 0) {
                    if (isset($services[$name])) {
                        $services[$name] += $monthlyEquivalent;
                    } else {
                        $services[$name] = $monthlyEquivalent;
                    }
                }
            }
        }
        
        // Sort by amount and get top 10
        arsort($services);
        $services = array_slice($services, 0, 10, true);
        
        return [
            'labels' => array_keys($services),
            'data' => array_values($services)
        ];
    }
    
    private function getCurrencyBreakdown($subscriptions) {
        $currencies = [];
        
        foreach ($subscriptions as $sub) {
            if (strtolower($sub['status'] ?? 'active') === 'active') {
                $currency = strtoupper($sub['currency'] ?? 'USD');
                $amount = floatval($sub['amount'] ?? 0);
                $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                
                // Convert to monthly equivalent for comparison
                $monthlyEquivalent = 0;
                switch ($cycle) {
                    case 'monthly':
                        $monthlyEquivalent = $amount;
                        break;
                    case 'yearly':
                        $monthlyEquivalent = $amount / 12;
                        break;
                    case 'one-time':
                        $monthlyEquivalent = 0; // Don't include one-time in recurring comparison
                        break;
                }
                
                if ($monthlyEquivalent > 0) {
                    if (isset($currencies[$currency])) {
                        $currencies[$currency] += $monthlyEquivalent;
                    } else {
                        $currencies[$currency] = $monthlyEquivalent;
                    }
                }
            }
        }
        
        return [
            'labels' => array_keys($currencies),
            'data' => array_values($currencies)
        ];
    }
    
    private function getSubscriptionGrowth($subscriptions, $from_date, $to_date) {
        $months = [];
        $growth = [];
        
        // Calculate months between from_date and to_date, max 12 months
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $interval = $start->diff($end);
        $monthsCount = min(12, $interval->m + ($interval->y * 12) + 1);
        
        // Generate months within the date range
        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $date = clone $end;
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Count subscriptions created up to this month
            $count = 0;
            foreach ($subscriptions as $sub) {
                $createdDate = new DateTime($sub['created_at'] ?? 'now');
                if ($createdDate <= $date) {
                    $count++;
                }
            }
            $growth[] = $count;
        }
        
        return ['labels' => $months, 'data' => $growth];
    }
    
    /**
     * Get monthly spending trend for all data (last 12 months)
     */
    private function getMonthlySpendingTrendAll($subscriptions) {
        $months = [];
        $spending = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Calculate monthly equivalent spending for this period
            $monthlyEquivalent = 0;
            foreach ($subscriptions as $sub) {
                $subDate = new DateTime($sub['created_at']);
                if ($subDate <= $date && strtolower($sub['status'] ?? 'active') === 'active') {
                    $amount = floatval($sub['amount'] ?? 0);
                    $cycle = strtolower($sub['billing_cycle'] ?? 'monthly');
                    
                    // Convert to monthly equivalent for comparison
                    $subMonthlyEquivalent = 0;
                    switch ($cycle) {
                        case 'monthly':
                            $subMonthlyEquivalent = $amount;
                            break;
                        case 'yearly':
                            $subMonthlyEquivalent = $amount / 12;
                            break;
                        case 'one-time':
                            $subMonthlyEquivalent = 0; // Don't include one-time in recurring comparison
                            break;
                    }
                    $monthlyEquivalent += $subMonthlyEquivalent;
                }
            }
            $spending[] = round($monthlyEquivalent, 2);
        }
        
        return ['labels' => $months, 'data' => $spending];
    }
    
    /**
     * Get subscription growth for all data (last 12 months)
     */
    private function getSubscriptionGrowthAll($subscriptions) {
        $months = [];
        $growth = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Count subscriptions created up to this month
            $count = 0;
            foreach ($subscriptions as $sub) {
                $createdDate = new DateTime($sub['created_at'] ?? 'now');
                if ($createdDate <= $date) {
                    $count++;
                }
            }
            $growth[] = $count;
        }
        
        return ['labels' => $months, 'data' => $growth];
    }
    
    /**
     * Handle AJAX requests for dashboard data with date filtering
     */
    public function ajaxStats() {
        if (!ErrorHandler::isAjaxRequest()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
        
        return ErrorHandler::wrap(function() {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                ErrorHandler::handleAjaxError('User not authenticated', 401);
                return;
            }
            
            // Get date parameters
            $from_date = $_GET['from'] ?? null;
            $to_date = $_GET['to'] ?? null;
            
            // Only apply date filtering if both dates are provided
            $applyDateFilter = $from_date && $to_date;
            
            if ($applyDateFilter) {
                // Validate dates
                $from_date = $this->validateDate($from_date) ? $from_date : null;
                $to_date = $this->validateDate($to_date) ? $to_date : null;
                $applyDateFilter = $from_date && $to_date;
            }
            
            // Get data based on whether filtering is applied
            if ($applyDateFilter) {
                $stats = $this->getOptimizedStats($userId, $from_date, $to_date);
                $analytics = $this->getOptimizedAnalytics($userId, $from_date, $to_date);
                $expenseStats = $this->getOptimizedExpenseStats($from_date, $to_date);
                $expenseAnalytics = $this->getOptimizedExpenseAnalytics($from_date, $to_date);
            } else {
                $stats = $this->getAllStats($userId);
                $analytics = $this->getAllAnalytics($userId);
                $expenseStats = $this->getAllExpenseStats();
                $expenseAnalytics = $this->getAllExpenseAnalytics();
                $from_date = '';
                $to_date = '';
            }
            
            // Add debug info in development
            $debug = [];
            if (Config::get('debug', false)) {
                $debug = [
                    'user_id' => $userId,
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'raw_subscriptions_count' => $this->db->count("subscriptions"),
                    'filtered_subscriptions_count' => $this->db->count("subscriptions", [
                        "created_at[>=]" => $from_date . ' 00:00:00',
                        "created_at[<=]" => $to_date . ' 23:59:59'
                    ]),
                    'total_subscriptions_in_db' => $this->db->count("subscriptions"),
                    'session_data' => $_SESSION ?? 'no session'
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'analytics' => $analytics,
                    'expense_stats' => $expenseStats,
                    'expense_analytics' => $expenseAnalytics
                ],
                'debug' => $debug
            ]);
        });
    }
    
    /**
     * Get transaction analytics data
     */
    private function getTransactionAnalytics($from_date = null, $to_date = null) {
        try {
            // Get unified transaction data for analytics
            if ($from_date && $to_date) {
                $transactions = $this->transactionModel->getAllWithCompleteInfoAndDateFilter($from_date, $to_date);
            } else {
                $transactions = $this->transactionModel->getAllWithCompleteInfo();
            }
            
            return [
                'monthly_revenue_trend' => $this->getMonthlyRevenueTrend($transactions),
                'transaction_status_distribution' => $this->getTransactionStatusDistribution($transactions),
                'transaction_type_distribution' => $this->getTransactionTypeDistribution($transactions),
                'payment_method_distribution' => $this->getPaymentMethodDistribution($transactions),
                'top_revenue_items' => $this->getTopRevenueItems($transactions)
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Error getting transaction analytics", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'monthly_revenue_trend' => ['labels' => [], 'data' => []],
                'transaction_status_distribution' => ['successful' => 0, 'failed' => 0, 'pending' => 0],
                'transaction_type_distribution' => ['subscription' => 0, 'expense' => 0],
                'payment_method_distribution' => [],
                'top_revenue_items' => ['labels' => [], 'data' => []]
            ];
        }
    }
    
    private function getMonthlyRevenueTrend($transactions) {
        $monthlyRevenue = [];
        $months = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $monthKey = $date->format('Y-m');
            $months[] = $date->format('M Y');
            $monthlyRevenue[$monthKey] = 0;
        }
        
        // Calculate revenue for each month
        foreach ($transactions as $transaction) {
            if ($transaction['status'] === 'successful') {
                $transactionDate = new DateTime($transaction['transaction_date']);
                $monthKey = $transactionDate->format('Y-m');
                
                if (isset($monthlyRevenue[$monthKey])) {
                    $monthlyRevenue[$monthKey] += floatval($transaction['amount']);
                }
            }
        }
        
        return [
            'labels' => $months,
            'data' => array_values($monthlyRevenue)
        ];
    }
    
    private function getTransactionStatusDistribution($transactions) {
        $distribution = ['successful' => 0, 'failed' => 0, 'pending' => 0];
        
        foreach ($transactions as $transaction) {
            $status = $transaction['status'] ?? 'failed';
            if (isset($distribution[$status])) {
                $distribution[$status]++;
            }
        }
        
        return $distribution;
    }

    private function getTransactionTypeDistribution($transactions) {
        $distribution = ['subscription' => 0, 'expense' => 0];
        
        foreach ($transactions as $transaction) {
            $type = $transaction['transaction_type'] ?? 'subscription';
            if (isset($distribution[$type])) {
                $distribution[$type]++;
            }
        }
        
        return $distribution;
    }
    
    private function getPaymentMethodDistribution($transactions) {
        $methods = [];
        
        foreach ($transactions as $transaction) {
            $method = $transaction['payment_method_type'] ?? 'unknown';
            if (isset($methods[$method])) {
                $methods[$method]++;
            } else {
                $methods[$method] = 1;
            }
        }
        
        return [
            'labels' => array_keys($methods),
            'data' => array_values($methods)
        ];
    }
    
    private function getTopRevenueItems($transactions) {
        $itemRevenue = [];
        
        foreach ($transactions as $transaction) {
            if ($transaction['status'] === 'successful') {
                // Get item name based on transaction type
                $itemName = 'Unknown';
                if ($transaction['transaction_type'] === 'subscription') {
                    $itemName = $transaction['subscription_name'] ?? 'Unknown Subscription';
                } else {
                    $itemName = $transaction['expense_title'] ?? 'Unknown Expense';
                }
                
                if (isset($itemRevenue[$itemName])) {
                    $itemRevenue[$itemName] += floatval($transaction['amount']);
                } else {
                    $itemRevenue[$itemName] = floatval($transaction['amount']);
                }
            }
        }
        
        // Sort by revenue and get top 10
        arsort($itemRevenue);
        $topItems = array_slice($itemRevenue, 0, 10, true);
        
        return [
            'labels' => array_keys($topItems),
            'data' => array_values($topItems)
        ];
    }

    /**
     * Get all expense statistics without date filtering
     */
    private function getAllExpenseStats() {
        try {
            // Get all expense statistics without user filtering (for centralized system)
            $totalExpenses = $this->db->count("expenses");
            $pendingExpenses = $this->db->count("expenses", ["status" => "pending"]);
            $approvedExpenses = $this->db->count("expenses", ["status" => "approved"]);
            $rejectedExpenses = $this->db->count("expenses", ["status" => "rejected"]);
            $paidExpenses = $this->db->count("expenses", ["status" => "paid"]);
            
            // Calculate total amounts
            $totalAmount = $this->db->sum("expenses", "amount") ?: 0;
            $totalTax = $this->db->sum("expenses", "tax_amount") ?: 0;
            
            // Calculate approved and paid amounts
            $approvedAmount = $this->db->sum("expenses", "amount", ["status" => ["approved", "paid"]]) ?: 0;
            $paidAmount = $this->db->sum("expenses", "amount", ["status" => "paid"]) ?: 0;
            
            // Average expense amount
            $avgExpenseAmount = $totalExpenses > 0 ? ($totalAmount / $totalExpenses) : 0;
            
            return [
                'total_expenses' => $totalExpenses,
                'pending_expenses' => $pendingExpenses,
                'approved_expenses' => $approvedExpenses,
                'rejected_expenses' => $rejectedExpenses,
                'paid_expenses' => $paidExpenses,
                'total_amount' => floatval($totalAmount),
                'total_tax' => floatval($totalTax),
                'total_with_tax' => floatval($totalAmount + $totalTax),
                'approved_amount' => floatval($approvedAmount),
                'paid_amount' => floatval($paidAmount),
                'avg_expense_amount' => floatval($avgExpenseAmount)
            ];
            
        } catch (Exception $e) {
            // Fallback to empty stats if query fails
            return [
                'total_expenses' => 0,
                'pending_expenses' => 0,
                'approved_expenses' => 0,
                'rejected_expenses' => 0,
                'paid_expenses' => 0,
                'total_amount' => 0,
                'total_tax' => 0,
                'total_with_tax' => 0,
                'approved_amount' => 0,
                'paid_amount' => 0,
                'avg_expense_amount' => 0
            ];
        }
    }

    /**
     * Get optimized expense statistics with date filtering
     */
    private function getOptimizedExpenseStats($from_date, $to_date) {
        try {
            $whereConditions = [
                "expense_date[>=]" => $from_date,
                "expense_date[<=]" => $to_date
            ];
            
            // Get basic counts
            $totalExpenses = $this->db->count("expenses", $whereConditions);
            
            $pendingConditions = array_merge($whereConditions, ["status" => "pending"]);
            $pendingExpenses = $this->db->count("expenses", $pendingConditions);
            
            $approvedConditions = array_merge($whereConditions, ["status" => "approved"]);
            $approvedExpenses = $this->db->count("expenses", $approvedConditions);
            
            $rejectedConditions = array_merge($whereConditions, ["status" => "rejected"]);
            $rejectedExpenses = $this->db->count("expenses", $rejectedConditions);
            
            $paidConditions = array_merge($whereConditions, ["status" => "paid"]);
            $paidExpenses = $this->db->count("expenses", $paidConditions);
            
            // Calculate total amounts
            $totalAmount = $this->db->sum("expenses", "amount", $whereConditions) ?: 0;
            $totalTax = $this->db->sum("expenses", "tax_amount", $whereConditions) ?: 0;
            
            // Calculate approved and paid amounts
            $approvedAmount = $this->db->sum("expenses", "amount", $approvedConditions) ?: 0;
            $paidAmount = $this->db->sum("expenses", "amount", $paidConditions) ?: 0;
            
            // Average expense amount
            $avgExpenseAmount = $totalExpenses > 0 ? ($totalAmount / $totalExpenses) : 0;
            
            return [
                'total_expenses' => $totalExpenses,
                'pending_expenses' => $pendingExpenses,
                'approved_expenses' => $approvedExpenses,
                'rejected_expenses' => $rejectedExpenses,
                'paid_expenses' => $paidExpenses,
                'total_amount' => floatval($totalAmount),
                'total_tax' => floatval($totalTax),
                'total_with_tax' => floatval($totalAmount + $totalTax),
                'approved_amount' => floatval($approvedAmount),
                'paid_amount' => floatval($paidAmount),
                'avg_expense_amount' => floatval($avgExpenseAmount)
            ];
            
        } catch (Exception $e) {
            // Log the error for debugging
            AppLogger::error('Expense stats calculation failed', [
                'error' => $e->getMessage(),
                'from_date' => $from_date,
                'to_date' => $to_date
            ]);
            
            // Fallback to empty stats if query fails
            return [
                'total_expenses' => 0,
                'pending_expenses' => 0,
                'approved_expenses' => 0,
                'rejected_expenses' => 0,
                'paid_expenses' => 0,
                'total_amount' => 0,
                'total_tax' => 0,
                'total_with_tax' => 0,
                'approved_amount' => 0,
                'paid_amount' => 0,
                'avg_expense_amount' => 0
            ];
        }
    }

    /**
     * Get all expense analytics without date filtering
     */
    private function getAllExpenseAnalytics() {
        try {
            // Get all expenses for analytics (without user filtering for centralized system)
            $expenses = $this->db->select("expenses", "*");
            
            return [
                'status_distribution' => $this->getExpenseStatusDistribution($expenses),
                'category_breakdown' => $this->getExpenseCategoryBreakdown($expenses),
                'monthly_expense_trend' => $this->getMonthlyExpenseTrendAll($expenses),
                'top_categories' => $this->getTopExpenseCategories($expenses),
                'payment_method_distribution' => $this->getExpensePaymentMethodDistribution($expenses),
                'expense_growth' => $this->getExpenseGrowthAll($expenses),
                'currency_breakdown' => $this->getExpenseCurrencyBreakdown($expenses)
            ];
            
        } catch (Exception $e) {
            // Return empty analytics if query fails
            return [
                'status_distribution' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'paid' => 0],
                'category_breakdown' => ['labels' => [], 'data' => []],
                'monthly_expense_trend' => ['labels' => [], 'data' => []],
                'top_categories' => ['labels' => [], 'data' => []],
                'payment_method_distribution' => ['labels' => [], 'data' => []],
                'expense_growth' => ['labels' => [], 'data' => []],
                'currency_breakdown' => ['labels' => [], 'data' => []]
            ];
        }
    }

    /**
     * Optimized expense analytics data with date filtering
     */
    private function getOptimizedExpenseAnalytics($from_date, $to_date) {
        try {
            // Get filtered expenses for analytics
            $expenses = $this->db->select("expenses", "*", [
                "expense_date[>=]" => $from_date,
                "expense_date[<=]" => $to_date
            ]);
            
            return [
                'status_distribution' => $this->getExpenseStatusDistribution($expenses),
                'category_breakdown' => $this->getExpenseCategoryBreakdown($expenses),
                'monthly_expense_trend' => $this->getMonthlyExpenseTrend($expenses, $from_date, $to_date),
                'top_categories' => $this->getTopExpenseCategories($expenses),
                'payment_method_distribution' => $this->getExpensePaymentMethodDistribution($expenses),
                'expense_growth' => $this->getExpenseGrowth($expenses, $from_date, $to_date),
                'currency_breakdown' => $this->getExpenseCurrencyBreakdown($expenses)
            ];
            
        } catch (Exception $e) {
            // Return empty analytics if query fails
            return [
                'status_distribution' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'paid' => 0],
                'category_breakdown' => ['labels' => [], 'data' => []],
                'monthly_expense_trend' => ['labels' => [], 'data' => []],
                'top_categories' => ['labels' => [], 'data' => []],
                'payment_method_distribution' => ['labels' => [], 'data' => []],
                'expense_growth' => ['labels' => [], 'data' => []],
                'currency_breakdown' => ['labels' => [], 'data' => []]
            ];
        }
    }

    private function getExpenseStatusDistribution($expenses) {
        $distribution = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'paid' => 0];
        
        foreach ($expenses as $expense) {
            $status = strtolower($expense['status'] ?? 'pending');
            if (isset($distribution[$status])) {
                $distribution[$status]++;
            }
        }
        
        return $distribution;
    }

    private function getExpenseCategoryBreakdown($expenses) {
        $categories = [];
        
        // Get category information with expense data
        foreach ($expenses as $expense) {
            if ($expense['category_id']) {
                $category = $this->db->get('categories', ['name', 'color'], ['id' => $expense['category_id']]);
                $categoryName = $category['name'] ?? 'Unknown';
                $amount = floatval($expense['amount'] ?? 0);
                
                if (isset($categories[$categoryName])) {
                    $categories[$categoryName] += $amount;
                } else {
                    $categories[$categoryName] = $amount;
                }
            }
        }
        
        // Sort by amount and get top 10
        arsort($categories);
        $categories = array_slice($categories, 0, 10, true);
        
        return [
            'labels' => array_keys($categories),
            'data' => array_values($categories)
        ];
    }

    private function getMonthlyExpenseTrend($expenses, $from_date, $to_date) {
        $months = [];
        $spending = [];
        
        // Calculate months between from_date and to_date, max 12 months
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $interval = $start->diff($end);
        $monthsCount = min(12, $interval->m + ($interval->y * 12) + 1);
        
        // Generate months within the date range
        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $date = clone $end;
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Calculate total spending for this month
            $monthTotal = 0;
            foreach ($expenses as $expense) {
                $expenseDate = new DateTime($expense['expense_date']);
                if ($expenseDate->format('Y-m') === $date->format('Y-m')) {
                    $monthTotal += floatval($expense['amount'] ?? 0);
                }
            }
            $spending[] = round($monthTotal, 2);
        }
        
        return ['labels' => $months, 'data' => $spending];
    }

    private function getMonthlyExpenseTrendAll($expenses) {
        $months = [];
        $spending = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Calculate total spending for this month
            $monthTotal = 0;
            foreach ($expenses as $expense) {
                $expenseDate = new DateTime($expense['expense_date']);
                if ($expenseDate->format('Y-m') === $date->format('Y-m')) {
                    $monthTotal += floatval($expense['amount'] ?? 0);
                }
            }
            $spending[] = round($monthTotal, 2);
        }
        
        return ['labels' => $months, 'data' => $spending];
    }

    private function getTopExpenseCategories($expenses) {
        $categories = [];
        
        // Get category information with expense data
        foreach ($expenses as $expense) {
            if ($expense['category_id']) {
                $category = $this->db->get('categories', ['name'], ['id' => $expense['category_id']]);
                $categoryName = $category['name'] ?? 'Unknown';
                $amount = floatval($expense['amount'] ?? 0);
                
                if (isset($categories[$categoryName])) {
                    $categories[$categoryName] += $amount;
                } else {
                    $categories[$categoryName] = $amount;
                }
            } else {
                // Handle uncategorized expenses
                $amount = floatval($expense['amount'] ?? 0);
                if (isset($categories['Uncategorized'])) {
                    $categories['Uncategorized'] += $amount;
                } else {
                    $categories['Uncategorized'] = $amount;
                }
            }
        }
        
        // Sort by amount and get top 10
        arsort($categories);
        $categories = array_slice($categories, 0, 10, true);
        
        return [
            'labels' => array_keys($categories),
            'data' => array_values($categories)
        ];
    }

    private function getExpensePaymentMethodDistribution($expenses) {
        $methods = [];
        
        foreach ($expenses as $expense) {
            $method = $expense['payment_method_type'] ?? 'unknown';
            if (isset($methods[$method])) {
                $methods[$method]++;
            } else {
                $methods[$method] = 1;
            }
        }
        
        return [
            'labels' => array_keys($methods),
            'data' => array_values($methods)
        ];
    }

    private function getExpenseGrowth($expenses, $from_date, $to_date) {
        $months = [];
        $growth = [];
        
        // Calculate months between from_date and to_date, max 12 months
        $start = new DateTime($from_date);
        $end = new DateTime($to_date);
        $interval = $start->diff($end);
        $monthsCount = min(12, $interval->m + ($interval->y * 12) + 1);
        
        // Generate months within the date range
        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $date = clone $end;
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Count expenses created up to this month
            $count = 0;
            foreach ($expenses as $expense) {
                $createdDate = new DateTime($expense['created_at'] ?? 'now');
                if ($createdDate <= $date) {
                    $count++;
                }
            }
            $growth[] = $count;
        }
        
        return ['labels' => $months, 'data' => $growth];
    }

    private function getExpenseGrowthAll($expenses) {
        $months = [];
        $growth = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $months[] = $date->format('M Y');
            
            // Count expenses created up to this month
            $count = 0;
            foreach ($expenses as $expense) {
                $createdDate = new DateTime($expense['created_at'] ?? 'now');
                if ($createdDate <= $date) {
                    $count++;
                }
            }
            $growth[] = $count;
        }
        
        return ['labels' => $months, 'data' => $growth];
    }

    private function getExpenseCurrencyBreakdown($expenses) {
        $currencies = [];
        
        foreach ($expenses as $expense) {
            if (strtolower($expense['status'] ?? 'pending') !== 'rejected') {
                $currency = strtoupper($expense['currency'] ?? 'USD');
                $amount = floatval($expense['amount'] ?? 0);
                
                if ($amount > 0) {
                    if (isset($currencies[$currency])) {
                        $currencies[$currency] += $amount;
                    } else {
                        $currencies[$currency] = $amount;
                    }
                }
            }
        }
        
        return [
            'labels' => array_keys($currencies),
            'data' => array_values($currencies)
        ];
    }
} 