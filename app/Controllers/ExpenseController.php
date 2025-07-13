<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Expense.php';
require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Models/Tag.php';
require_once __DIR__ . '/../Models/CreditCard.php';
require_once __DIR__ . '/../Models/BankAccount.php';
require_once __DIR__ . '/../Models/CryptoWallet.php';

class ExpenseController extends Controller {

    private $expenseModel;
    private $categoryModel;
    private $tagModel;
    private $creditCardModel;
    private $bankAccountModel;
    private $cryptoWalletModel;

    public function __construct($db) {
        $this->expenseModel = new Expense($db);
        $this->categoryModel = new Category($db);
        $this->tagModel = new Tag($db);
        $this->creditCardModel = new CreditCard($db);
        $this->bankAccountModel = new BankAccount($db);
        $this->cryptoWalletModel = new CryptoWallet($db);
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
        
        // Add cache control headers to prevent stale data display
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Get filter parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'tag_id' => $_GET['tag_id'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'payment_id' => $_GET['payment_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'amount_min' => $_GET['amount_min'] ?? null,
            'amount_max' => $_GET['amount_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        // Get all expenses for centralized system (no pagination for DataTable client-side processing)
        $expenses = $this->expenseModel->getAllExpensesWithFilters($filters, 1, 10000); // Large limit to get all records
        $totalCount = $this->expenseModel->countAllExpensesWithFilters($filters);
        

        
        // Get filter options - all data for centralized system
        $categories = $this->categoryModel->getAllWithUserInfo();
        $tags = $this->tagModel->getAllWithUserInfo();
        $creditCards = $this->creditCardModel->getAllWithUserInfo();
        $bankAccounts = $this->bankAccountModel->getAllWithUserInfo();
        $cryptoWallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        // Get summary statistics for all expenses
        $stats = $this->expenseModel->getAllExpenseStats($filters);
        
        // Map stats to what the view expects
        $stats['total_count'] = $stats['total_expenses'];
        $stats['pending_count'] = $stats['pending_expenses'];
        $stats['approved_count'] = $stats['approved_expenses'];
        
        $this->view('dashboard/expenses/index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'tags' => $tags,
            'creditCards' => $creditCards,
            'bankAccounts' => $bankAccounts,
            'cryptoWallets' => $cryptoWallets,
            'stats' => $stats,
            'filters' => $filters,
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total_count' => $totalCount,
                'limit' => $totalCount
            ],
            'load_datatable' => true,
            'datatable_target' => '#expenses-table'
        ]);
    }

    public function create() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        
        // Get all data for centralized system - not user-specific
        $categories = $this->categoryModel->getAllWithUserInfo();
        $tags = $this->tagModel->getAllWithUserInfo();
        $creditCards = $this->creditCardModel->getAllWithUserInfo();
        $bankAccounts = $this->bankAccountModel->getAllWithUserInfo();
        $cryptoWallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        // Check if there's at least one payment method available
        if (empty($creditCards) && empty($bankAccounts) && empty($cryptoWallets)) {
            FlashMessage::warning('You need to add at least one payment method (credit card, bank account, or crypto wallet) before creating an expense.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        $this->view('dashboard/expenses/create', [
            'categories' => $categories,
            'tags' => $tags,
            'creditCards' => $creditCards,
            'bankAccounts' => $bankAccounts,
            'cryptoWallets' => $cryptoWallets,
            'currencies' => BankAccount::getSupportedCurrencies(),
            'taxRates' => [
                '0' => '0% (No Tax)',
                '5' => '5%',
                '8' => '8%',
                '10' => '10%',
                '15' => '15%',
                '20' => '20%',
                '25' => '25%'
            ]
        ]);
    }

    public function store() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $currency = trim($_POST['currency'] ?? 'USD');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $tagIds = $_POST['tag_ids'] ?? [];
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $paymentId = !empty($_POST['payment_id']) ? (int)$_POST['payment_id'] : null;
        $expenseDate = $_POST['expense_date'] ?? '';
        $dueDate = $_POST['due_date'] ?? null;
        $taxRate = floatval($_POST['tax_rate'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        
        // Validation
        if (empty($title)) {
            FlashMessage::error('Title is required.');
            header('Location: /expenses/create');
            exit();
        }
        
        if ($amount <= 0) {
            FlashMessage::error('Amount must be greater than 0.');
            header('Location: /expenses/create');
            exit();
        }
        
        if (empty($paymentMethod)) {
            FlashMessage::error('Payment method is required.');
            header('Location: /expenses/create');
            exit();
        }
        
        if (empty($paymentId)) {
            FlashMessage::error('Payment method selection is required.');
            header('Location: /expenses/create');
            exit();
        }
        
        if (empty($expenseDate)) {
            FlashMessage::error('Expense date is required.');
            header('Location: /expenses/create');
            exit();
        }
        
        // Validate payment method exists (no ownership check for centralized system)
        $paymentValid = false;
        switch ($paymentMethod) {
            case 'credit_card':
                $card = $this->creditCardModel->find($paymentId);
                $paymentValid = ($card !== null);
                break;
            case 'bank_account':
                $account = $this->bankAccountModel->find($paymentId);
                $paymentValid = ($account !== null);
                break;
            case 'crypto_wallet':
                $wallet = $this->cryptoWalletModel->find($paymentId);
                $paymentValid = ($wallet !== null);
                break;
        }
        
        if (!$paymentValid) {
            FlashMessage::error('Invalid payment method selected.');
            header('Location: /expenses/create');
            exit();
        }
        
        // Validate category if provided (no ownership check for centralized system)
        if ($categoryId) {
            $category = $this->categoryModel->find($categoryId);
            if (!$category) {
                FlashMessage::error('Invalid category selected.');
                header('Location: /expenses/create');
                exit();
            }
        }
        
        // Validate tags if provided (no ownership check for centralized system)
        if (!empty($tagIds)) {
            foreach ($tagIds as $tagId) {
                $tag = $this->tagModel->find($tagId);
                if (!$tag) {
                    FlashMessage::error('Invalid tag selected.');
                    header('Location: /expenses/create');
                    exit();
                }
            }
        }
        
        // Validate dates
        if (!strtotime($expenseDate)) {
            FlashMessage::error('Invalid expense date.');
            header('Location: /expenses/create');
            exit();
        }
        
        if ($dueDate && !strtotime($dueDate)) {
            FlashMessage::error('Invalid due date.');
            header('Location: /expenses/create');
            exit();
        }
        
        // Calculate tax amount
        $taxAmount = ($taxRate > 0) ? ($amount * $taxRate / 100) : 0;
        $totalAmount = $amount + $taxAmount;
        
                // Handle file upload
        $attachments = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachmentPath = $this->handleFileUpload($_FILES['attachment'], $userId);
            if (!$attachmentPath) {
                FlashMessage::error('Failed to upload attachment.');
                header('Location: /expenses/create');
                exit();
            }
            $attachments = json_encode([$attachmentPath]);
        }

        // Map payment method to correct columns
        $paymentData = [];
        switch ($paymentMethod) {
            case 'credit_card':
                $paymentData['credit_card_id'] = $paymentId;
                $paymentData['bank_account_id'] = null;
                $paymentData['crypto_wallet_id'] = null;
                break;
            case 'bank_account':
                $paymentData['credit_card_id'] = null;
                $paymentData['bank_account_id'] = $paymentId;
                $paymentData['crypto_wallet_id'] = null;
                break;
            case 'crypto_wallet':
                $paymentData['credit_card_id'] = null;
                $paymentData['bank_account_id'] = null;
                $paymentData['crypto_wallet_id'] = $paymentId;
                break;
        }

        $data = [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'category_id' => $categoryId,
            'payment_method_type' => $paymentMethod,
            'credit_card_id' => $paymentData['credit_card_id'],
            'bank_account_id' => $paymentData['bank_account_id'],
            'crypto_wallet_id' => $paymentData['crypto_wallet_id'],
            'expense_date' => $expenseDate,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'notes' => $notes,
            'status' => $status,
            'attachments' => $attachments
        ];
        
        try {
            $expenseId = $this->expenseModel->create($data);
            
            if ($expenseId) {
                // Add tags if provided
                if (!empty($tagIds)) {
                    $this->expenseModel->addTags($expenseId, $tagIds);
                }
                
                // Generate transaction if expense is approved
                if ($status === 'approved') {
                    $this->expenseModel->generateTransaction($expenseId);
                }
                
                AppLogger::info('Expense created', [
                    'user_id' => $userId,
                    'expense_id' => $expenseId,
                    'title' => $title,
                    'amount' => $totalAmount
                ]);
                
                FlashMessage::success('Expense created successfully!');
                header('Location: /expenses');
            } else {
                FlashMessage::error('Failed to create expense. Please try again.');
                header('Location: /expenses/create');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create expense', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create expense. Please try again.');
            header('Location: /expenses/create');
        }
        
        exit();
    }

    public function edit($id) {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        $expense = $this->expenseModel->findWithRelations($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        // Get all data for centralized system - not user-specific
        $categories = $this->categoryModel->getAllWithUserInfo();
        $tags = $this->tagModel->getAllWithUserInfo();
        $creditCards = $this->creditCardModel->getAllWithUserInfo();
        $bankAccounts = $this->bankAccountModel->getAllWithUserInfo();
        $cryptoWallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        // Check if there's at least one payment method available
        if (empty($creditCards) && empty($bankAccounts) && empty($cryptoWallets)) {
            FlashMessage::warning('You need to have at least one payment method (credit card, bank account, or crypto wallet) to edit expenses.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        // Get expense tags
        $expenseTags = $this->expenseModel->getExpenseTags($id);
        $selectedTagIds = array_column($expenseTags, 'id');
        
        $this->view('dashboard/expenses/edit', [
            'expense' => $expense,
            'categories' => $categories,
            'tags' => $tags,
            'creditCards' => $creditCards,
            'bankAccounts' => $bankAccounts,
            'cryptoWallets' => $cryptoWallets,
            'selectedTagIds' => $selectedTagIds,
            'currencies' => BankAccount::getSupportedCurrencies(),
            'taxRates' => [
                '0' => '0% (No Tax)',
                '5' => '5%',
                '8' => '8%',
                '10' => '10%',
                '15' => '15%',
                '20' => '20%',
                '25' => '25%'
            ]
        ]);
    }

    public function update($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses/' . $id . '/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        // Similar validation as store method
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $currency = trim($_POST['currency'] ?? 'USD');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $tagIds = $_POST['tag_ids'] ?? [];
        $expenseDate = $_POST['expense_date'] ?? '';
        $taxRate = floatval($_POST['tax_rate'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        
        // Validation (same as store method)
        if (empty($title)) {
            FlashMessage::error('Title is required.');
            header('Location: /expenses/' . $id . '/edit');
            exit();
        }
        
        if ($amount <= 0) {
            FlashMessage::error('Amount must be greater than 0.');
            header('Location: /expenses/' . $id . '/edit');
            exit();
        }
        
        if (empty($expenseDate)) {
            FlashMessage::error('Expense date is required.');
            header('Location: /expenses/' . $id . '/edit');
            exit();
        }
        
        // Calculate tax amount
        $taxAmount = ($taxRate > 0) ? ($amount * $taxRate / 100) : 0;
        $totalAmount = $amount + $taxAmount;
        
                // Handle file upload
        $attachments = $expense['attachments'];
        if (!empty($_FILES['attachment']['name'])) {
            $newAttachmentPath = $this->handleFileUpload($_FILES['attachment'], $userId);
            if ($newAttachmentPath) {
                // Delete old attachments if they exist
                if ($attachments) {
                    $oldAttachments = json_decode($attachments, true);
                    if (is_array($oldAttachments)) {
                        foreach ($oldAttachments as $oldPath) {
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    }
                }
                $attachments = json_encode([$newAttachmentPath]);
            }
        }

        // Use existing payment method data (don't change payment method in edit)
        $data = [
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'category_id' => $categoryId,
            'expense_date' => $expenseDate,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'notes' => $notes,
            'status' => $status,
            'attachments' => $attachments
        ];
        
        try {
            $result = $this->expenseModel->update($id, $data);
            
            if ($result) {
                // Update tags
                $this->expenseModel->removeTags($id);
                if (!empty($tagIds)) {
                    $this->expenseModel->addTags($id, $tagIds);
                }
                
                // Handle transaction generation based on status change
                if ($status === 'approved' && $expense['status'] !== 'approved') {
                    $this->expenseModel->generateTransaction($id);
                } elseif ($status !== 'approved' && $expense['status'] === 'approved') {
                    $this->expenseModel->removeTransaction($id);
                }
                
                AppLogger::info('Expense updated', [
                    'user_id' => $userId,
                    'expense_id' => $id,
                    'title' => $title
                ]);
                
                FlashMessage::success('Expense updated successfully!');
            } else {
                FlashMessage::error('No changes were made.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to update expense', [
                'user_id' => $userId,
                'expense_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to update expense. Please try again.');
        }
        
        header('Location: /expenses');
        exit();
    }

    public function delete($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        try {
            $result = $this->expenseModel->delete($id);
            
            if ($result) {
                // Delete attachments if they exist
                if ($expense['attachments']) {
                    $attachments = json_decode($expense['attachments'], true);
                    if (is_array($attachments)) {
                        foreach ($attachments as $attachmentPath) {
                            if (file_exists($attachmentPath)) {
                                unlink($attachmentPath);
                            }
                        }
                    }
                }
                
                AppLogger::info('Expense deleted', [
                    'user_id' => $userId,
                    'expense_id' => $id,
                    'title' => $expense['title']
                ]);
                
                FlashMessage::success('Expense deleted successfully!');
            } else {
                FlashMessage::error('Failed to delete expense. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete expense', [
                'user_id' => $userId,
                'expense_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete expense. Please try again.');
        }
        
        header('Location: /expenses');
        exit();
    }

    public function show($id) {
        $this->checkAuthentication();
        
        // Get expense with creator information
        $expense = $this->expenseModel->findWithRelations($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        // Get related data
        $category = $expense['category_id'] ? $this->categoryModel->find($expense['category_id']) : null;
        $tags = $this->expenseModel->getExpenseTags($id);
        $transaction = $this->expenseModel->getTransaction($id);
        
        $this->view('dashboard/expenses/view', [
            'expense' => $expense,
            'category' => $category,
            'tags' => $tags,
            'transaction' => $transaction
        ]);
    }

    /**
     * Import expenses from Excel file
     */
    public function import() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        
        $categories = $this->categoryModel->getAllWithUserInfo();
        $tags = $this->tagModel->getAllWithUserInfo();
        $creditCards = $this->creditCardModel->getAllWithUserInfo();
        $bankAccounts = $this->bankAccountModel->getAllWithUserInfo();
        $cryptoWallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        // Check if there's at least one payment method available
        if (empty($creditCards) && empty($bankAccounts) && empty($cryptoWallets)) {
            FlashMessage::warning('You need to have at least one payment method (credit card, bank account, or crypto wallet) to import expenses.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        $this->view('dashboard/expenses/import', [
            'categories' => $categories,
            'tags' => $tags,
            'creditCards' => $creditCards,
            'bankAccounts' => $bankAccounts,
            'cryptoWallets' => $cryptoWallets
        ]);
    }

    /**
     * Process Excel import
     */
    public function processImport() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses/import');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate file upload
        if (empty($_FILES['excel_file']['tmp_name']) || !is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
            FlashMessage::error('Please select an Excel file to import.');
            header('Location: /expenses/import');
            exit();
        }
        
        $file = $_FILES['excel_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            FlashMessage::error('File upload failed. Please try again.');
            header('Location: /expenses/import');
            exit();
        }
        
        // Validate file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            FlashMessage::error('Please upload a valid Excel file (.xls, .xlsx) or CSV file (.csv).');
            header('Location: /expenses/import');
            exit();
        }
        
        // Get actual file MIME type using finfo (more secure than $_FILES['type'])
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedTypes = [
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/csv',
            'text/plain' // CSV files sometimes have this MIME type
        ];
        
        if (!in_array($mimeType, $allowedTypes)) {
            FlashMessage::error('Please upload a valid Excel file (.xls, .xlsx) or CSV file (.csv).');
            header('Location: /expenses/import');
            exit();
        }
        
        // Check PHP upload limits instead of hardcoded limit
        $maxUpload = (int)(ini_get('upload_max_filesize'));
        $maxPost = (int)(ini_get('post_max_size'));
        $maxFilesize = min($maxUpload, $maxPost) * 1024 * 1024;
        
        if ($file['size'] > $maxFilesize) {
            $sizeMB = round($maxFilesize / (1024 * 1024));
            FlashMessage::error("File size exceeds PHP limit of {$sizeMB}MB. Please check your PHP upload settings.");
            header('Location: /expenses/import');
            exit();
        }
        
        try {
            $result = $this->expenseModel->importFromExcel($file['tmp_name'], $userId, $_POST);
            
            if ($result['success']) {
                AppLogger::info('Expenses imported from Excel', [
                    'user_id' => $userId,
                    'imported_count' => $result['imported_count'],
                    'skipped_count' => $result['skipped_count'],
                    'file_name' => $file['name'],
                    'file_size' => $file['size']
                ]);
                
                $message = "Successfully imported {$result['imported_count']} expenses.";
                if ($result['skipped_count'] > 0) {
                    $message .= " {$result['skipped_count']} rows were skipped.";
                }
                if (!empty($result['errors'])) {
                    $message .= " Some issues were found: " . implode(', ', array_slice($result['errors'], 0, 3));
                    if (count($result['errors']) > 3) {
                        $message .= " and " . (count($result['errors']) - 3) . " more.";
                    }
                }
                

                
                FlashMessage::success($message);
                // Add cache busting headers to ensure fresh data is loaded
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('Location: /expenses?imported=' . time());
            } else {
                AppLogger::warning('Excel import failed', [
                    'user_id' => $userId,
                    'error' => $result['error'],
                    'file_name' => $file['name'],
                    'file_size' => $file['size']
                ]);
                
                FlashMessage::error('Import failed: ' . ($result['error'] ?? 'Unknown error'));
                header('Location: /expenses/import');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to import expenses', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file_name' => $file['name'] ?? 'unknown',
                'file_size' => $file['size'] ?? 0,
                'trace' => $e->getTraceAsString()
            ]);
            FlashMessage::error('Failed to import expenses: ' . $e->getMessage());
            header('Location: /expenses/import');
        }
        
        exit();
    }

    /**
     * Export expenses to Excel
     */
    public function export() {
        $this->checkAuthentication();
        
        // Get filter parameters
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'tag_id' => $_GET['tag_id'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'payment_id' => $_GET['payment_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'amount_min' => $_GET['amount_min'] ?? null,
            'amount_max' => $_GET['amount_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        try {
            // Export all expenses for centralized system
            $filePath = $this->expenseModel->exportToExcel(null, $filters);
            
            if ($filePath && file_exists($filePath)) {
                // Create safe path using only basename to prevent path traversal
                $tempDir = sys_get_temp_dir();
                $safeFileName = basename($filePath);
                $safeFilePath = $tempDir . DIRECTORY_SEPARATOR . $safeFileName;
                
                // Verify the safe path exists and is the same as original
                if (!file_exists($safeFilePath) || realpath($safeFilePath) !== realpath($filePath)) {
                    throw new Exception('Invalid file path');
                }
                
                $fileName = 'expenses_' . date('Y-m-d_H-i-s') . '.xlsx';
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Content-Length: ' . filesize($safeFilePath));
                
                readfile($safeFilePath);
                
                // Clean up temporary file
                unlink($safeFilePath);
                exit();
            } else {
                FlashMessage::error('Failed to generate export file.');
                header('Location: /expenses');
                exit();
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to export expenses', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to export expenses. Please try again.');
            header('Location: /expenses');
            exit();
        }
    }

    /**
     * Approve expense (change status to approved and generate transaction)
     */
    public function approve($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        try {
            $result = $this->expenseModel->approve($id);
            
            if ($result) {
                FlashMessage::success('Expense approved successfully!');
            } else {
                FlashMessage::error('Failed to approve expense.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to approve expense', [
                'user_id' => $userId,
                'expense_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to approve expense. Please try again.');
        }
        
        header('Location: /expenses');
        exit();
    }

    /**
     * Reject expense (change status to rejected and remove transaction)
     */
    public function reject($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /expenses');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        try {
            $result = $this->expenseModel->reject($id);
            
            if ($result) {
                FlashMessage::success('Expense rejected successfully!');
            } else {
                FlashMessage::error('Failed to reject expense.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to reject expense', [
                'user_id' => $userId,
                'expense_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to reject expense. Please try again.');
        }
        
        header('Location: /expenses');
        exit();
    }

    /**
     * Get expense analytics (AJAX endpoint)
     */
    public function analytics() {
        $this->checkAuthentication();
        
        $period = $_GET['period'] ?? 'month';
        
        // Get analytics for all expenses in centralized system
        $analytics = $this->expenseModel->getAnalytics(null, $period);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'analytics' => $analytics
        ]);
        exit();
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($id) {
        $this->checkAuthentication();
        
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            FlashMessage::error('Expense not found.');
            header('Location: /expenses');
            exit();
        }
        
        if (!$expense['attachments']) {
            FlashMessage::error('Attachment not found.');
            header('Location: /expenses');
            exit();
        }
        
        $attachments = json_decode($expense['attachments'], true);
        if (!is_array($attachments) || empty($attachments)) {
            FlashMessage::error('Attachment not found.');
            header('Location: /expenses');
            exit();
        }
        
        $attachmentPath = $attachments[0]; // Get first attachment
        
        // Security check - ensure file path is within uploads directory
        $uploadsDir = realpath('uploads/');
        $realAttachmentPath = realpath($attachmentPath);
        
        if (!$realAttachmentPath || !$uploadsDir || strpos($realAttachmentPath, $uploadsDir) !== 0) {
            FlashMessage::error('Invalid file path.');
            header('Location: /expenses');
            exit();
        }
        
        if (!file_exists($realAttachmentPath)) {
            FlashMessage::error('Attachment file not found.');
            header('Location: /expenses');
            exit();
        }
        
        // Validate file type for security
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($realAttachmentPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            FlashMessage::error('Invalid file type.');
            header('Location: /expenses');
            exit();
        }
        
        // Sanitize filename for download
        $fileName = basename($realAttachmentPath);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8') . '"');
        header('Content-Length: ' . filesize($realAttachmentPath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($realAttachmentPath);
        exit();
    }

    /**
     * Download Excel template for import
     */
    public function downloadTemplate() {
        $this->checkAuthentication();
        
        try {
            // Create new spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $sheet->setCellValue('A1', 'Title');
            $sheet->setCellValue('B1', 'Amount');
            $sheet->setCellValue('C1', 'Category');
            $sheet->setCellValue('D1', 'Date');
            $sheet->setCellValue('E1', 'Description');
            $sheet->setCellValue('F1', 'Currency');
            $sheet->setCellValue('G1', 'Tax Rate');
            $sheet->setCellValue('H1', 'Notes');
            $sheet->setCellValue('I1', 'Tags');
            
            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF']
                ]
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Add sample data
            $sampleData = [
                ['Office Supplies', '150.00', 'Office', '2024-01-15', 'Pens, paper, and other office supplies', 'USD', '8.25', 'Purchased from Office Depot', 'business,office'],
                ['Business Lunch', '75.50', 'Meals', '2024-01-16', 'Client meeting lunch', 'USD', '0', 'Meeting with potential client', 'client,food'],
                ['Software License', '299.99', 'Software', '2024-01-17', 'Annual subscription to project management tool', 'USD', '0', 'Required for team collaboration', 'software,tools'],
                ['Fuel', '45.20', 'Transportation', '2024-01-18', 'Gas for business trip', 'USD', '0', 'Trip to client site', 'travel,fuel'],
                ['Hotel Stay', '180.00', 'Travel', '2024-01-19', 'One night hotel stay', 'USD', '12.50', 'Business conference attendance', 'travel,conference']
            ];
            
            // Add sample data to sheet
            $row = 2;
            foreach ($sampleData as $data) {
                $sheet->setCellValue('A' . $row, $data[0]);
                $sheet->setCellValue('B' . $row, $data[1]);
                $sheet->setCellValue('C' . $row, $data[2]);
                $sheet->setCellValue('D' . $row, $data[3]);
                $sheet->setCellValue('E' . $row, $data[4]);
                $sheet->setCellValue('F' . $row, $data[5]);
                $sheet->setCellValue('G' . $row, $data[6]);
                $sheet->setCellValue('H' . $row, $data[7]);
                $sheet->setCellValue('I' . $row, $data[8]);
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            $fileName = 'expense_import_template_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            // Output file
            $writer->save('php://output');
            
        } catch (Exception $e) {
            AppLogger::error('Failed to generate expense template', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to generate template. Please try again.');
            header('Location: /expenses/import');
        }
        
        exit();
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $userId) {
        // Validate file exists and has no errors
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Get actual file MIME type using finfo (more secure than $_FILES['type'])
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Validate file extension
        $originalName = basename($file['name']);
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return false;
        }
        
        // Check PHP upload limits instead of hardcoded limit
        $maxUpload = (int)(ini_get('upload_max_filesize'));
        $maxPost = (int)(ini_get('post_max_size'));
        $maxFilesize = min($maxUpload, $maxPost) * 1024 * 1024;
        
        if ($file['size'] > $maxFilesize) {
            return false;
        }
        
        // Sanitize user ID to prevent path traversal
        $sanitizedUserId = (int)$userId;
        if ($sanitizedUserId <= 0) {
            return false;
        }
        
        $uploadDir = 'uploads/expenses/' . $sanitizedUserId . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return false;
            }
        }
        
        // Generate secure filename
        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $filePath = $uploadDir . $fileName;
        
        // Additional security check - ensure the path is within uploads directory
        $realUploadDir = realpath($uploadDir);
        $realFilePath = realpath(dirname($filePath)) . '/' . basename($filePath);
        
        if (!$realUploadDir || strpos($realFilePath, $realUploadDir) !== 0) {
            return false;
        }
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $filePath;
        }
        
        return false;
    }
} 