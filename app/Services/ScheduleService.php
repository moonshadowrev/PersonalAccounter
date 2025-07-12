<?php

require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/../Models/Subscription.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Config.php';

class ScheduleService {
    
    private $db;
    private $transactionService;
    private $subscriptionModel;
    
    public function __construct($db) {
        $this->db = $db;
        $this->transactionService = new TransactionService($db);
        $this->subscriptionModel = new Subscription($db);
    }
    
    /**
     * Process all due payments (called by cron job)
     */
    public function processDuePayments() {
        $today = date('Y-m-d');
        $processedCount = 0;
        $failedCount = 0;
        $results = [];
        
        try {
            // Get all active subscriptions that are due for payment
            $dueSubscriptions = $this->getDueSubscriptions($today);
            
                         AppLogger::info("Processing due payments", [
                'date' => $today,
                'due_subscriptions_count' => count($dueSubscriptions)
            ]);
            
            foreach ($dueSubscriptions as $subscription) {
                $result = $this->processSubscriptionPayment($subscription);
                $results[] = $result;
                
                if ($result['success']) {
                    $processedCount++;
                } else {
                    $failedCount++;
                }
                
                // Small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second
            }
            
            $summary = [
                'total_due' => count($dueSubscriptions),
                'processed' => $processedCount,
                'failed' => $failedCount,
                'results' => $results
            ];
            
            AppLogger::info("Due payments processing completed", $summary);
            
            return $summary;
            
        } catch (Exception $e) {
            AppLogger::error("Error processing due payments", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'total_due' => 0,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get subscriptions that are due for payment
     */
    private function getDueSubscriptions($date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($date) {
            return $this->db->select('subscriptions', '*', [
                'status' => 'active',
                'billing_cycle[!]' => 'one-time',
                'next_payment_date[<=]' => $date,
                'ORDER' => ['next_payment_date' => 'ASC']
            ]);
        }, []);
    }
    
    /**
     * Process payment for a single subscription
     */
    private function processSubscriptionPayment($subscription) {
        try {
            $result = $this->transactionService->processTransaction($subscription['id']);
            
            // Log the result
            AppLogger::info("Subscription payment processed", [
                'subscription_id' => $subscription['id'],
                'subscription_name' => $subscription['name'],
                'amount' => $subscription['amount'],
                'success' => $result['success']
            ]);
            
            return array_merge($result, [
                'subscription_id' => $subscription['id'],
                'subscription_name' => $subscription['name'],
                'amount' => $subscription['amount']
            ]);
            
        } catch (Exception $e) {
            AppLogger::error("Error processing subscription payment", [
                'subscription_id' => $subscription['id'],
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'subscription_id' => $subscription['id'],
                'subscription_name' => $subscription['name'],
                'amount' => $subscription['amount'],
                'message' => 'Processing error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process upcoming payments (within next 7 days)
     */
    public function processUpcomingPayments($days = 7) {
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        $today = date('Y-m-d');
        
        $upcomingSubscriptions = ErrorHandler::wrapDatabaseOperation(function() use ($today, $endDate) {
            return $this->db->select('subscriptions', '*', [
                'status' => 'active',
                'billing_cycle[!]' => 'one-time',
                'next_payment_date[>=]' => $today,
                'next_payment_date[<=]' => $endDate,
                'ORDER' => ['next_payment_date' => 'ASC']
            ]);
        }, []);
        
        return [
            'upcoming_count' => count($upcomingSubscriptions),
            'subscriptions' => $upcomingSubscriptions,
            'date_range' => [
                'from' => $today,
                'to' => $endDate
            ]
        ];
    }
    
    /**
     * Handle expired subscriptions
     */
    public function handleExpiredSubscriptions() {
        $today = date('Y-m-d');
        $expiredCount = 0;
        
        try {
            // Find subscriptions that are overdue by more than 30 days
            $overdueDate = date('Y-m-d', strtotime('-30 days'));
            
            $overdueSubscriptions = ErrorHandler::wrapDatabaseOperation(function() use ($overdueDate) {
                return $this->db->select('subscriptions', '*', [
                    'status' => 'active',
                    'billing_cycle[!]' => 'one-time',
                    'next_payment_date[<]' => $overdueDate
                ]);
            }, []);
            
            foreach ($overdueSubscriptions as $subscription) {
                // Mark as expired
                $this->subscriptionModel->update($subscription['id'], [
                    'status' => 'expired'
                ]);
                
                $expiredCount++;
                
                AppLogger::info("Subscription marked as expired", [
                    'subscription_id' => $subscription['id'],
                    'subscription_name' => $subscription['name'],
                    'last_payment_date' => $subscription['next_payment_date']
                ]);
            }
            
            return [
                'expired_count' => $expiredCount,
                'subscriptions' => $overdueSubscriptions
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Error handling expired subscriptions", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'expired_count' => $expiredCount,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate payment schedule for a subscription
     */
    public function generatePaymentSchedule($subscriptionId, $months = 12) {
        $subscription = $this->subscriptionModel->find($subscriptionId);
        
        if (!$subscription || $subscription['billing_cycle'] === 'one-time') {
            return [];
        }
        
        $schedule = [];
        $currentDate = new DateTime($subscription['next_payment_date'] ?: 'now');
        
        for ($i = 0; $i < $months; $i++) {
            $schedule[] = [
                'date' => $currentDate->format('Y-m-d'),
                'amount' => $subscription['amount'],
                'currency' => $subscription['currency'],
                'billing_cycle' => $subscription['billing_cycle']
            ];
            
            // Calculate next payment date
            switch ($subscription['billing_cycle']) {
                case 'weekly':
                    $currentDate->add(new DateInterval('P1W'));
                    break;
                case 'monthly':
                    $currentDate->add(new DateInterval('P1M'));
                    break;
                case 'quarterly':
                    $currentDate->add(new DateInterval('P3M'));
                    break;
                case 'yearly':
                    $currentDate->add(new DateInterval('P1Y'));
                    break;
            }
        }
        
        return $schedule;
    }
    
    /**
     * Get payment statistics for scheduling
     */
    public function getScheduleStats() {
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $nextMonth = date('Y-m-d', strtotime('+30 days'));
        
        return [
            'due_today' => $this->countDueSubscriptions($today, $today),
            'due_this_week' => $this->countDueSubscriptions($today, $nextWeek),
            'due_this_month' => $this->countDueSubscriptions($today, $nextMonth),
            'overdue' => $this->countOverdueSubscriptions($today),
            'active_recurring' => $this->countActiveRecurringSubscriptions()
        ];
    }
    
    private function countDueSubscriptions($fromDate, $toDate) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($fromDate, $toDate) {
            return $this->db->count('subscriptions', [
                'status' => 'active',
                'billing_cycle[!]' => 'one-time',
                'next_payment_date[>=]' => $fromDate,
                'next_payment_date[<=]' => $toDate
            ]);
        }, 0);
    }
    
    private function countOverdueSubscriptions($date) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($date) {
            return $this->db->count('subscriptions', [
                'status' => 'active',
                'billing_cycle[!]' => 'one-time',
                'next_payment_date[<]' => $date
            ]);
        }, 0);
    }
    
    private function countActiveRecurringSubscriptions() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->count('subscriptions', [
                'status' => 'active',
                'billing_cycle[!]' => 'one-time'
            ]);
        }, 0);
    }
} 