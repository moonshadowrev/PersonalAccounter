<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Subscription.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/Expense.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller {

    private $subscriptionModel;
    private $transactionModel;
    private $expenseModel;

    public function __construct($db) {
        $this->subscriptionModel = new Subscription($db);
        $this->transactionModel = new Transaction($db);
        $this->expenseModel = new Expense($db);
    }

    public function index() {
        // Parse query parameters directly from REQUEST_URI since $_GET is corrupted
        $query_params = [];
        if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            $query_string = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
            parse_str($query_string, $query_params);
        }
        
        // Date filter - get from parsed query parameters
        $from_date = $query_params['from'] ?? null;
        $to_date = $query_params['to'] ?? null;
        
        // Store original values for form display
        $original_from = $from_date;
        $original_to = $to_date;
        
        // Only apply date filtering if both dates are provided
        $applyDateFilter = $from_date && $to_date;
        
        if ($applyDateFilter) {
            // Validate dates
            $from_date = $this->validateDate($from_date) ? $from_date : null;
            $to_date = $this->validateDate($to_date) ? $to_date : null;
            $applyDateFilter = $from_date && $to_date;
        }

        // Get unified transaction data based on whether filtering is applied
        if ($applyDateFilter) {
            $transactions = $this->transactionModel->getAllWithCompleteInfoAndDateFilter($from_date, $to_date);
            $transactionStats = $this->transactionModel->getTransactionStats($from_date, $to_date);
        } else {
            // Show all data without date filtering
            $transactions = $this->transactionModel->getAllWithCompleteInfo();
            $transactionStats = $this->transactionModel->getTransactionStats();
        }
        
        // Process transactions to format data for display
        $processedTransactions = $this->processTransactionsForDisplay($transactions);
        
        $this->view('dashboard/reports/index', [
            'transactions' => $processedTransactions,
            'transaction_stats' => $transactionStats,
            'filter_dates' => ['from' => $original_from ?? '', 'to' => $original_to ?? ''],
            'load_datatable' => true,
            'datatable_target' => '#transactions-table'
        ]);
    }
    
    /**
     * Process transactions to format data for unified display
     */
    private function processTransactionsForDisplay($transactions) {
        $processed = [];
        
        foreach ($transactions as $transaction) {
            $processed[] = [
                'id' => $transaction['id'],
                'user_name' => $transaction['user_name'],
                'user_email' => $transaction['user_email'],
                'transaction_type' => $transaction['transaction_type'],
                'item_name' => $transaction['transaction_type'] === 'subscription' 
                    ? $transaction['subscription_name'] 
                    : $transaction['expense_title'],
                'vendor' => $transaction['transaction_type'] === 'expense' 
                    ? $transaction['expense_vendor'] 
                    : null,
                'category_name' => $transaction['category_name'],
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'status' => $transaction['status'],
                'transaction_date' => $transaction['transaction_date'],
                'payment_method_type' => $transaction['payment_method_type'],
                'payment_method_name' => $this->getPaymentMethodName($transaction),
                'billing_cycle' => $transaction['billing_cycle'], // Only for subscriptions
                'reference_number' => $transaction['reference_number'],
                'description' => $transaction['description'],
                'notes' => $transaction['notes']
            ];
        }
        
        return $processed;
    }
    
    /**
     * Get payment method name based on type
     */
    private function getPaymentMethodName($transaction) {
        switch ($transaction['payment_method_type']) {
            case 'credit_card':
                return $transaction['credit_card_name'];
            case 'bank_account':
                return $transaction['bank_account_name'];
            case 'crypto_wallet':
                return $transaction['crypto_wallet_name'];
            default:
                return ucfirst($transaction['payment_method_type'] ?? 'Unknown');
        }
    }
    
    /**
     * Validate date format
     */
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function export() {
        try {
            // Parse query parameters directly from REQUEST_URI
            $query_params = [];
            if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
                $query_string = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
                parse_str($query_string, $query_params);
            }
            
            // Apply same date filtering as index page
            $from_date = $query_params['from'] ?? null;
            $to_date = $query_params['to'] ?? null;
            
            // Only apply date filtering if both dates are provided
            $applyDateFilter = $from_date && $to_date;
            
            if ($applyDateFilter) {
                // Validate dates
                $from_date = $this->validateDate($from_date) ? $from_date : null;
                $to_date = $this->validateDate($to_date) ? $to_date : null;
                $applyDateFilter = $from_date && $to_date;
            }

            // Get unified transactions based on filtering
            if ($applyDateFilter) {
                $transactions = $this->transactionModel->getAllWithCompleteInfoAndDateFilter($from_date, $to_date);
                $filename = "unified_transactions_report_{$from_date}_to_{$to_date}.xlsx";
            } else {
                $transactions = $this->transactionModel->getAllWithCompleteInfo();
                $filename = "unified_transactions_report_" . date('Y-m-d') . ".xlsx";
            }

            if (empty($transactions)) {
                FlashMessage::warning('No transactions found to export for the selected date range.');
                header('Location: /reports');
                exit();
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers for unified transaction report
            $sheet->setCellValue('A1', 'Transaction ID');
            $sheet->setCellValue('B1', 'Type');
            $sheet->setCellValue('C1', 'User Name');
            $sheet->setCellValue('D1', 'User Email');
            $sheet->setCellValue('E1', 'Item/Service Name');
            $sheet->setCellValue('F1', 'Vendor');
            $sheet->setCellValue('G1', 'Category');
            $sheet->setCellValue('H1', 'Amount');
            $sheet->setCellValue('I1', 'Currency');
            $sheet->setCellValue('J1', 'Status');
            $sheet->setCellValue('K1', 'Payment Method');
            $sheet->setCellValue('L1', 'Payment Method Name');
            $sheet->setCellValue('M1', 'Transaction Date');
            $sheet->setCellValue('N1', 'Billing Cycle');
            $sheet->setCellValue('O1', 'Reference Number');
            $sheet->setCellValue('P1', 'Description');

            // Process and set data
            $processedTransactions = $this->processTransactionsForDisplay($transactions);
            $row = 2;
            foreach ($processedTransactions as $transaction) {
                $sheet->setCellValue('A' . $row, $transaction['id']);
                $sheet->setCellValue('B' . $row, ucfirst($transaction['transaction_type']));
                $sheet->setCellValue('C' . $row, $transaction['user_name']);
                $sheet->setCellValue('D' . $row, $transaction['user_email']);
                $sheet->setCellValue('E' . $row, $transaction['item_name']);
                $sheet->setCellValue('F' . $row, $transaction['vendor'] ?? '');
                $sheet->setCellValue('G' . $row, $transaction['category_name'] ?? '');
                $sheet->setCellValue('H' . $row, $transaction['amount']);
                $sheet->setCellValue('I' . $row, $transaction['currency']);
                $sheet->setCellValue('J' . $row, ucfirst($transaction['status']));
                $sheet->setCellValue('K' . $row, ucfirst($transaction['payment_method_type'] ?? ''));
                $sheet->setCellValue('L' . $row, $transaction['payment_method_name']);
                $sheet->setCellValue('M' . $row, $transaction['transaction_date']);
                $sheet->setCellValue('N' . $row, ucfirst($transaction['billing_cycle'] ?? ''));
                $sheet->setCellValue('O' . $row, $transaction['reference_number']);
                $sheet->setCellValue('P' . $row, $transaction['description']);
                $row++;
            }

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit();
        } catch (Exception $e) {
            FlashMessage::error('Failed to export report. Please try again.');
            header('Location: /reports');
            exit();
        }
    }
} 