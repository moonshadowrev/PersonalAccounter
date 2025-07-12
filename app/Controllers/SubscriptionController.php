<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Subscription.php';
require_once __DIR__ . '/../Models/CreditCard.php';
require_once __DIR__ . '/../Services/TransactionService.php';

class SubscriptionController extends Controller {

    private $subscriptionModel;
    private $creditCardModel;
    private $transactionService;

    public function __construct($db) {
        $this->subscriptionModel = new Subscription($db);
        $this->creditCardModel = new CreditCard($db);
        $this->transactionService = new TransactionService($db);
    }

    public function index() {
        // For now, get all records since DataTables handles pagination client-side
        // In the future, this could be optimized with server-side pagination for very large datasets
        $subscriptions = $this->subscriptionModel->getAllWithUserInfo();
        $this->view('dashboard/subscriptions/index', [
            'subscriptions' => $subscriptions,
            'load_datatable' => true,
            'datatable_target' => '#subscriptions-table'
        ]);
    }

    public function create() {
        $creditCards = $this->creditCardModel->getAll('*', ['ORDER' => ['created_at' => 'DESC']]);
        
        if (empty($creditCards)) {
            FlashMessage::warning('You need to add at least one credit card before creating a subscription.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        $this->view('dashboard/subscriptions/create', ['credit_cards' => $creditCards]);
    }

    public function store() {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /subscriptions/create');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['amount']) || empty($_POST['currency']) || 
            empty($_POST['billing_cycle']) || empty($_POST['credit_card_id'])) {
            FlashMessage::error('Please fill in all required fields.');
            header('Location: /subscriptions/create');
            exit();
        }
        
        // Validate amount is numeric and positive
        if (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
            FlashMessage::error('Please enter a valid amount greater than 0.');
            header('Location: /subscriptions/create');
            exit();
        }
        
        // Validate next payment date only for recurring subscriptions
        if ($_POST['billing_cycle'] !== 'one-time') {
            if (empty($_POST['next_payment_date'])) {
                FlashMessage::error('Next payment date is required for recurring subscriptions.');
                header('Location: /subscriptions/create');
                exit();
            }
            
            if (!strtotime($_POST['next_payment_date'])) {
                FlashMessage::error('Please enter a valid next payment date.');
                header('Location: /subscriptions/create');
                exit();
            }
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Verify the credit card exists
        $creditCard = $this->creditCardModel->find($_POST['credit_card_id']);
        if (!$creditCard) {
            FlashMessage::error('Invalid credit card selected.');
            header('Location: /subscriptions/create');
            exit();
        }
        
        // Prepare data for subscription creation
        $data = [
            'user_id' => $userId,
            'credit_card_id' => $_POST['credit_card_id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'amount' => $_POST['amount'],
            'currency' => $_POST['currency'],
            'billing_cycle' => $_POST['billing_cycle'],
            'status' => $_POST['status'] ?? 'active',
        ];
        
        // Set next payment date only for recurring subscriptions
        if ($_POST['billing_cycle'] === 'one-time') {
            $data['next_payment_date'] = null;
        } else {
            $data['next_payment_date'] = $_POST['next_payment_date'];
        }
        
        try {
            $result = $this->subscriptionModel->create($data);
            if ($result) {
                // If it's a one-time payment, process the transaction immediately
                if ($_POST['billing_cycle'] === 'one-time') {
                    $transactionResult = $this->transactionService->processOneTimePayment($result);
                    if ($transactionResult['success']) {
                        FlashMessage::success('One-time payment processed successfully!');
                    } else {
                        FlashMessage::warning('Subscription created but payment failed: ' . $transactionResult['message']);
                    }
                } else {
                    FlashMessage::success('Subscription created successfully!');
                }
            } else {
                FlashMessage::error('Failed to create subscription. Please try again.');
            }
        } catch (Exception $e) {
            FlashMessage::error('Failed to create subscription: ' . $e->getMessage());
        }
        
        header('Location: /subscriptions');
        exit();
    }

    public function edit($id) {
        $subscription = $this->subscriptionModel->find($id);
        
        if (!$subscription) {
            FlashMessage::error('Subscription not found.');
            header('Location: /subscriptions');
            exit();
        }
        
        $creditCards = $this->creditCardModel->getAll('*', ['ORDER' => ['created_at' => 'DESC']]);
        
        if (empty($creditCards)) {
            FlashMessage::warning('You need to have at least one credit card to edit subscriptions.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        $this->view('dashboard/subscriptions/edit', [
            'subscription' => $subscription,
            'credit_cards' => $creditCards
        ]);
    }

    public function update($id) {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /subscriptions/' . $id . '/edit');
            exit();
        }
        
        // Check if subscription exists
        $subscription = $this->subscriptionModel->find($id);
        if (!$subscription) {
            FlashMessage::error('Subscription not found.');
            header('Location: /subscriptions');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['amount']) || empty($_POST['currency']) || 
            empty($_POST['billing_cycle']) || empty($_POST['credit_card_id'])) {
            FlashMessage::error('Please fill in all required fields.');
            header('Location: /subscriptions/' . $id . '/edit');
            exit();
        }
        
        // Validate amount is numeric and positive
        if (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
            FlashMessage::error('Please enter a valid amount greater than 0.');
            header('Location: /subscriptions/' . $id . '/edit');
            exit();
        }
        
        // Validate next payment date only for recurring subscriptions
        if ($_POST['billing_cycle'] !== 'one-time') {
            if (empty($_POST['next_payment_date'])) {
                FlashMessage::error('Next payment date is required for recurring subscriptions.');
                header('Location: /subscriptions/' . $id . '/edit');
                exit();
            }
            
            if (!strtotime($_POST['next_payment_date'])) {
                FlashMessage::error('Please enter a valid next payment date.');
                header('Location: /subscriptions/' . $id . '/edit');
                exit();
            }
        }
        
        // Verify the credit card exists
        $creditCard = $this->creditCardModel->find($_POST['credit_card_id']);
        if (!$creditCard) {
            FlashMessage::error('Invalid credit card selected.');
            header('Location: /subscriptions/' . $id . '/edit');
            exit();
        }
        
        // Prepare data for subscription update
        $data = [
            'credit_card_id' => $_POST['credit_card_id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'amount' => $_POST['amount'],
            'currency' => $_POST['currency'],
            'billing_cycle' => $_POST['billing_cycle'],
            'status' => $_POST['status'] ?? 'active',
        ];
        
        // Set next payment date only for recurring subscriptions
        if ($_POST['billing_cycle'] === 'one-time') {
            $data['next_payment_date'] = null;
        } else {
            $data['next_payment_date'] = $_POST['next_payment_date'];
        }
        
        try {
            $result = $this->subscriptionModel->update($id, $data);
            if ($result !== false) {
                FlashMessage::success('Subscription updated successfully!');
            } else {
                FlashMessage::error('Failed to update subscription. No changes were made.');
            }
        } catch (Exception $e) {
            FlashMessage::error('Failed to update subscription: ' . $e->getMessage());
        }
        
        header('Location: /subscriptions');
        exit();
    }

    public function delete($id) {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /subscriptions');
            exit();
        }
        
        // Check if subscription exists
        $subscription = $this->subscriptionModel->find($id);
        if (!$subscription) {
            FlashMessage::error('Subscription not found.');
            header('Location: /subscriptions');
            exit();
        }
        
        try {
            $result = $this->subscriptionModel->delete($id);
            if ($result !== false) {
                FlashMessage::success('Subscription deleted successfully!');
            } else {
                FlashMessage::error('Failed to delete subscription. No changes were made.');
            }
        } catch (Exception $e) {
            FlashMessage::error('Failed to delete subscription: ' . $e->getMessage());
        }
        
        header('Location: /subscriptions');
        exit();
    }
} 