<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/BankAccount.php';

class BankAccountController extends Controller {

    private $bankAccountModel;

    public function __construct($db) {
        $this->bankAccountModel = new BankAccount($db);
    }

    private function checkAuthentication() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit();
        }
    }

    public function index() {
        $this->checkAuthentication();
        
        // Show all bank accounts in centralized system - no user filtering
        $bankAccounts = $this->bankAccountModel->getAllWithUserInfo();
        
        $this->view('dashboard/bank-accounts/index', [
            'bankAccounts' => $bankAccounts,
            'load_datatable' => true,
            'datatable_target' => '#bank-accounts-table'
        ]);
    }

    public function create() {
        $this->checkAuthentication();
        
        $this->view('dashboard/bank-accounts/create', [
            'accountTypes' => BankAccount::getAccountTypes(),
            'currencies' => BankAccount::getSupportedCurrencies()
        ]);
    }

    public function store() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /bank-accounts/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountType = trim($_POST['account_type'] ?? 'checking');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $routingNumber = trim($_POST['routing_number'] ?? '');
        $currency = trim($_POST['currency'] ?? 'USD');
        $countryCode = trim($_POST['country_code'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $swiftBic = trim($_POST['swift_bic'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Account name is required.');
            header('Location: /bank-accounts/create');
            exit();
        }
        
        if (empty($bankName)) {
            FlashMessage::error('Bank name is required.');
            header('Location: /bank-accounts/create');
            exit();
        }
        
        if (empty($accountNumber)) {
            FlashMessage::error('Account number is required.');
            header('Location: /bank-accounts/create');
            exit();
        }
        
        // Validate account number format
        if (!BankAccount::validateAccountNumber($accountNumber)) {
            FlashMessage::error('Please enter a valid account number (8-17 digits).');
            header('Location: /bank-accounts/create');
            exit();
        }
        
        // Validate routing number if provided
        if (!empty($routingNumber) && !BankAccount::validateRoutingNumber($routingNumber)) {
            FlashMessage::error('Please enter a valid routing/sort code (6-15 digits).');
            header('Location: /bank-accounts/create');
            exit();
        }

        // Validate IBAN if provided
        if (!empty($iban) && !BankAccount::validateIban($iban)) {
            FlashMessage::error('Please enter a valid IBAN format.');
            header('Location: /bank-accounts/create');
            exit();
        }

        // Validate SWIFT/BIC if provided
        if (!empty($swiftBic) && !BankAccount::validateSwiftBic($swiftBic)) {
            FlashMessage::error('Please enter a valid SWIFT/BIC code format.');
            header('Location: /bank-accounts/create');
            exit();
        }

        // Validate account type
        $validTypes = array_keys(BankAccount::getAccountTypes());
        if (!in_array($accountType, $validTypes)) {
            $accountType = 'checking';
        }
        
        // Validate currency
        $validCurrencies = array_keys(BankAccount::getSupportedCurrencies());
        if (!in_array($currency, $validCurrencies)) {
            $currency = 'USD';
        }
        
        // Store only last 4 digits of account number
        $accountNumberLast4 = substr(preg_replace('/[\s\-]/', '', $accountNumber), -4);
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'bank_name' => $bankName,
            'account_type' => $accountType,
            'account_number_last4' => $accountNumberLast4,
            'routing_number' => $routingNumber,
            'currency' => $currency,
            'country_code' => $countryCode,
            'iban' => $iban,
            'swift_bic' => $swiftBic,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $accountId = $this->bankAccountModel->create($data);
            
            if ($accountId) {
                AppLogger::info('Bank account created', [
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'name' => $name,
                    'bank_name' => $bankName
                ]);
                FlashMessage::success('Bank account created successfully!');
                header('Location: /bank-accounts');
            } else {
                FlashMessage::error('Failed to create bank account. Please try again.');
                header('Location: /bank-accounts/create');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create bank account', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create bank account. Please try again.');
            header('Location: /bank-accounts/create');
        }
        
        exit();
    }

    public function edit($id) {
        $this->checkAuthentication();
        
        $account = $this->bankAccountModel->find($id);
        
        if (!$account) {
            FlashMessage::error('Bank account not found.');
            header('Location: /bank-accounts');
            exit();
        }
        
        $this->view('dashboard/bank-accounts/edit', [
            'account' => $account,
            'accountTypes' => BankAccount::getAccountTypes(),
            'currencies' => BankAccount::getSupportedCurrencies()
        ]);
    }

    public function update($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $account = $this->bankAccountModel->find($id);
        
        if (!$account) {
            FlashMessage::error('Bank account not found.');
            header('Location: /bank-accounts');
            exit();
        }
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountType = trim($_POST['account_type'] ?? 'checking');
        $routingNumber = trim($_POST['routing_number'] ?? '');
        $currency = trim($_POST['currency'] ?? 'USD');
        $countryCode = trim($_POST['country_code'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $swiftBic = trim($_POST['swift_bic'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Account name is required.');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }
        
        if (empty($bankName)) {
            FlashMessage::error('Bank name is required.');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }
        
        // Validate routing number if provided
        if (!empty($routingNumber) && !BankAccount::validateRoutingNumber($routingNumber)) {
            FlashMessage::error('Please enter a valid routing/sort code (6-15 digits).');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }

        // Validate IBAN if provided
        if (!empty($iban) && !BankAccount::validateIban($iban)) {
            FlashMessage::error('Please enter a valid IBAN format.');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }

        // Validate SWIFT/BIC if provided
        if (!empty($swiftBic) && !BankAccount::validateSwiftBic($swiftBic)) {
            FlashMessage::error('Please enter a valid SWIFT/BIC code format.');
            header('Location: /bank-accounts/' . $id . '/edit');
            exit();
        }

        // Validate account type
        $validTypes = array_keys(BankAccount::getAccountTypes());
        if (!in_array($accountType, $validTypes)) {
            $accountType = 'checking';
        }
        
        // Validate currency
        $validCurrencies = array_keys(BankAccount::getSupportedCurrencies());
        if (!in_array($currency, $validCurrencies)) {
            $currency = 'USD';
        }
        
        $data = [
            'name' => $name,
            'bank_name' => $bankName,
            'account_type' => $accountType,
            'routing_number' => $routingNumber,
            'currency' => $currency,
            'country_code' => $countryCode,
            'iban' => $iban,
            'swift_bic' => $swiftBic,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $result = $this->bankAccountModel->update($id, $data);
            
            if ($result) {
                AppLogger::info('Bank account updated', [
                    'user_id' => $userId,
                    'account_id' => $id,
                    'name' => $name
                ]);
                FlashMessage::success('Bank account updated successfully!');
            } else {
                FlashMessage::error('No changes were made.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to update bank account', [
                'user_id' => $userId,
                'account_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to update bank account. Please try again.');
        }
        
        header('Location: /bank-accounts');
        exit();
    }

    public function delete($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /bank-accounts');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $account = $this->bankAccountModel->find($id);
        
        if (!$account) {
            FlashMessage::error('Bank account not found.');
            header('Location: /bank-accounts');
            exit();
        }
        
        try {
            // Check if account is used in expenses or subscriptions
            $usedInExpenses = $this->bankAccountModel->isUsedInExpenses($id);
            $usedInSubscriptions = $this->bankAccountModel->isUsedInSubscriptions($id);
            
            if ($usedInExpenses || $usedInSubscriptions) {
                $usageTypes = [];
                if ($usedInExpenses) $usageTypes[] = 'expenses';
                if ($usedInSubscriptions) $usageTypes[] = 'subscriptions';
                
                FlashMessage::error('Cannot delete bank account that is used in ' . implode(' and ', $usageTypes) . '. Please reassign or delete them first.');
                header('Location: /bank-accounts');
                exit();
            }
            
            $result = $this->bankAccountModel->delete($id);
            
            if ($result) {
                AppLogger::info('Bank account deleted', [
                    'user_id' => $userId,
                    'account_id' => $id,
                    'name' => $account['name']
                ]);
                FlashMessage::success('Bank account deleted successfully!');
            } else {
                FlashMessage::error('Failed to delete bank account. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete bank account', [
                'user_id' => $userId,
                'account_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete bank account. Please try again.');
        }
        
        header('Location: /bank-accounts');
        exit();
    }

    /**
     * Get bank accounts for AJAX requests (for expense/subscription forms)
     */
    public function ajaxList() {
        $this->checkAuthentication();
        
        // Return all bank accounts for centralized system
        $currency = $_GET['currency'] ?? null;
        
        if ($currency) {
            // Note: This would need to be updated to search all accounts by currency
            $accounts = $this->bankAccountModel->getAllWithUserInfo();
            // Filter by currency
            $accounts = array_filter($accounts, function($account) use ($currency) {
                return $account['currency'] === $currency;
            });
        } else {
            $accounts = $this->bankAccountModel->getAllWithUserInfo();
        }
        
        // Add masked account number for display
        foreach ($accounts as &$account) {
            $account['account_number_masked'] = BankAccount::getMaskedAccountNumber($account['account_number_last4']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'accounts' => $accounts
        ]);
        exit();
    }

    /**
     * Get account details (AJAX endpoint)
     */
    public function details($id) {
        $this->checkAuthentication();
        
        $account = $this->bankAccountModel->find($id);
        
        if (!$account) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Account not found']);
            exit();
        }
        
        // Get usage statistics
        $stats = $this->bankAccountModel->getUsageStats($id);
        $account['stats'] = $stats;
        $account['account_number_masked'] = BankAccount::getMaskedAccountNumber($account['account_number_last4']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'account' => $account
        ]);
        exit();
    }

    /**
     * Search bank accounts (AJAX endpoint)
     */
    public function search() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        $query = trim($_GET['q'] ?? '');
        
        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'accounts' => []]);
            exit();
        }
        
        $accounts = $this->bankAccountModel->search($userId, $query);
        
        // Add masked account number for display
        foreach ($accounts as &$account) {
            $account['account_number_masked'] = BankAccount::getMaskedAccountNumber($account['account_number_last4']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'accounts' => $accounts
        ]);
        exit();
    }

    /**
     * Get accounts by currency (AJAX endpoint)
     */
    public function byCurrency($currency) {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        
        // Validate currency
        $validCurrencies = array_keys(BankAccount::getSupportedCurrencies());
        if (!in_array($currency, $validCurrencies)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid currency']);
            exit();
        }
        
        $accounts = $this->bankAccountModel->getByCurrency($userId, $currency);
        
        // Add masked account number for display
        foreach ($accounts as &$account) {
            $account['account_number_masked'] = BankAccount::getMaskedAccountNumber($account['account_number_last4']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'accounts' => $accounts
        ]);
        exit();
    }

    /**
     * Validate account number (AJAX endpoint)
     */
    public function validateAccount() {
        header('Content-Type: application/json');
        
        $accountNumber = trim($_POST['account_number'] ?? '');
        $routingNumber = trim($_POST['routing_number'] ?? '');
        
        $response = [
            'account_valid' => false,
            'routing_valid' => false,
            'messages' => []
        ];
        
        if (!empty($accountNumber)) {
            $response['account_valid'] = BankAccount::validateAccountNumber($accountNumber);
            if (!$response['account_valid']) {
                $response['messages'][] = 'Account number must be 8-17 digits';
            }
        }
        
        if (!empty($routingNumber)) {
            $response['routing_valid'] = BankAccount::validateRoutingNumber($routingNumber);
            if (!$response['routing_valid']) {
                $response['messages'][] = 'Routing number must be 6-15 digits (international format)';
            }
        }
        
        $response['success'] = $response['account_valid'] && ($response['routing_valid'] || empty($routingNumber));
        
        echo json_encode($response);
        exit();
    }
} 