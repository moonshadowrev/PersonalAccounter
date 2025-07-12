<?php

require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/Subscription.php';
require_once __DIR__ . '/../Models/Expense.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Config.php';

class TransactionService {
    
    private $db;
    private $transactionModel;
    private $subscriptionModel;
    private $expenseModel;
    
    public function __construct($db) {
        $this->db = $db;
        $this->transactionModel = new Transaction($db);
        $this->subscriptionModel = new Subscription($db);
        $this->expenseModel = new Expense($db);
    }
    
    /**
     * Process a transaction for a subscription
     */
    public function processSubscriptionTransaction($subscriptionId, $amount = null, $transactionDate = null) {
        try {
            // Get subscription details
            $subscription = $this->subscriptionModel->find($subscriptionId);
            if (!$subscription) {
                throw new Exception("Subscription not found");
            }
            
            // Use subscription amount if not provided
            $amount = $amount ?: $subscription['amount'];
            $transactionDate = $transactionDate ?: date('Y-m-d H:i:s');
            
            // Simulate payment processing (in real app, this would integrate with payment gateway)
            $isSuccessful = $this->simulatePaymentProcessing($subscription, $amount);
            
            // Create transaction record using new method
            $transactionId = $this->transactionModel->createSubscriptionTransaction(
                $subscription, 
                $amount, 
                $isSuccessful ? 'successful' : 'failed'
            );
            
            if ($transactionId && $isSuccessful) {
                // Update subscription's next payment date if successful
                $this->updateNextPaymentDate($subscriptionId, $subscription['billing_cycle']);
                
                AppLogger::info("Subscription transaction processed successfully", [
                    'transaction_id' => $transactionId,
                    'subscription_id' => $subscriptionId,
                    'amount' => $amount,
                    'status' => 'successful'
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message' => 'Subscription transaction processed successfully'
                ];
            } else {
                AppLogger::warning("Subscription transaction failed", [
                    'subscription_id' => $subscriptionId,
                    'amount' => $amount,
                    'status' => 'failed'
                ]);
                
                return [
                    'success' => false,
                    'transaction_id' => $transactionId,
                    'message' => 'Subscription transaction failed'
                ];
            }
            
        } catch (Exception $e) {
            AppLogger::error("Subscription transaction processing error", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Subscription transaction processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process a transaction for an expense
     */
    public function processExpenseTransaction($expenseId, $status = 'successful') {
        try {
            // Get expense details
            $expense = $this->expenseModel->find($expenseId);
            if (!$expense) {
                throw new Exception("Expense not found");
            }
            
            // Create transaction record using new method
            $transactionId = $this->transactionModel->createExpenseTransaction($expense, $status);
            
            if ($transactionId) {
                AppLogger::info("Expense transaction created successfully", [
                    'transaction_id' => $transactionId,
                    'expense_id' => $expenseId,
                    'amount' => $expense['amount'],
                    'status' => $status
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message' => 'Expense transaction created successfully'
                ];
            } else {
                AppLogger::warning("Expense transaction creation failed", [
                    'expense_id' => $expenseId,
                    'amount' => $expense['amount'],
                    'status' => $status
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Expense transaction creation failed'
                ];
            }
            
        } catch (Exception $e) {
            AppLogger::error("Expense transaction processing error", [
                'expense_id' => $expenseId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Expense transaction processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Legacy method for backward compatibility - now uses subscription transaction
     */
    public function processTransaction($subscriptionId, $amount = null, $transactionDate = null) {
        return $this->processSubscriptionTransaction($subscriptionId, $amount, $transactionDate);
    }
    
    /**
     * Process one-time payment (instant transaction)
     */
    public function processOneTimePayment($subscriptionId) {
        $subscription = $this->subscriptionModel->find($subscriptionId);
        
        if (!$subscription || $subscription['billing_cycle'] !== 'one-time') {
            return [
                'success' => false,
                'message' => 'Invalid one-time subscription'
            ];
        }
        
        // Process immediate transaction for one-time payments
        $result = $this->processSubscriptionTransaction($subscriptionId);
        
        if ($result['success']) {
            // Mark subscription as completed for one-time payments
            $this->subscriptionModel->update($subscriptionId, [
                'status' => 'expired',
                'next_payment_date' => null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get unified transaction statistics for reporting
     */
    public function getTransactionStats($from_date = null, $to_date = null) {
        return $this->transactionModel->getTransactionStats($from_date, $to_date);
    }

    /**
     * Get monthly transaction data for analytics
     */
    public function getMonthlyTransactionData($year = null) {
        return $this->transactionModel->getMonthlyTransactionData($year);
    }

    /**
     * Simulate payment processing (replace with real payment gateway integration)
     */
    private function simulatePaymentProcessing($subscription, $amount) {
        // Simulate 95% success rate for demo purposes
        // In real application, this would integrate with Stripe, PayPal, etc.
        return (rand(1, 100) <= 95);
    }
    
    /**
     * Update subscription's next payment date based on billing cycle
     */
    private function updateNextPaymentDate($subscriptionId, $billingCycle) {
        $nextDate = $this->calculateNextPaymentDate($billingCycle);
        
        if ($nextDate) {
            $this->subscriptionModel->update($subscriptionId, [
                'next_payment_date' => $nextDate
            ]);
        }
    }
    
    /**
     * Calculate next payment date based on billing cycle
     */
    private function calculateNextPaymentDate($billingCycle) {
        $today = new DateTime();
        
        switch ($billingCycle) {
            case 'monthly':
                return $today->add(new DateInterval('P1M'))->format('Y-m-d');
            case 'yearly':
                return $today->add(new DateInterval('P1Y'))->format('Y-m-d');
            case 'weekly':
                return $today->add(new DateInterval('P1W'))->format('Y-m-d');
            case 'quarterly':
                return $today->add(new DateInterval('P3M'))->format('Y-m-d');
            case 'one-time':
                return null; // No next payment for one-time subscriptions
            default:
                return null;
        }
    }
} 