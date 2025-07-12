<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/CreditCard.php';

class CreditCardController extends Controller {

    private $creditCardModel;

    public function __construct($db) {
        $this->creditCardModel = new CreditCard($db);
    }

    public function index() {
        // For now, get all records since DataTables handles pagination client-side
        // In the future, this could be optimized with server-side pagination for very large datasets
        $creditCards = $this->creditCardModel->getAllWithUserInfo();
        $this->view('dashboard/credit_cards/index', [
            'credit_cards' => $creditCards,
            'load_datatable' => true,
            'datatable_target' => '#credit-cards-table'
        ]);
    }

    public function create() {
        $this->view('dashboard/credit_cards/create');
    }

    public function store() {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['card_number']) || empty($_POST['expiry_month']) || empty($_POST['expiry_year'])) {
            FlashMessage::error('Please fill in all required fields.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        // Validate card number (basic check for digits and length)
        $cardNumber = preg_replace('/\s+/', '', $_POST['card_number']);
        if (!ctype_digit($cardNumber) || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            FlashMessage::error('Please enter a valid credit card number.');
            header('Location: /credit-cards/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $data = [
            'user_id' => $userId,
            'name' => $_POST['name'],
            'card_number_last4' => substr($cardNumber, -4),
            'expiry_month' => $_POST['expiry_month'],
            'expiry_year' => $_POST['expiry_year'],
        ];
        
        try {
            $this->creditCardModel->create($data);
            FlashMessage::success('Credit card added successfully!');
        } catch (Exception $e) {
            FlashMessage::error('Failed to add credit card. Please try again.');
        }
        
        header('Location: /credit-cards');
        exit();
    }

    public function edit($id) {
        $creditCard = $this->creditCardModel->find($id);
        
        if (!$creditCard) {
            FlashMessage::error('Credit card not found.');
            header('Location: /credit-cards');
            exit();
        }
        
        $this->view('dashboard/credit_cards/edit', ['credit_card' => $creditCard]);
    }

    public function update($id) {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /credit-cards/' . $id . '/edit');
            exit();
        }
        
        // Check if credit card exists
        $creditCard = $this->creditCardModel->find($id);
        if (!$creditCard) {
            FlashMessage::error('Credit card not found.');
            header('Location: /credit-cards');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['card_number']) || empty($_POST['expiry_month']) || empty($_POST['expiry_year'])) {
            FlashMessage::error('Please fill in all required fields.');
            header('Location: /credit-cards/' . $id . '/edit');
            exit();
        }
        
        // Validate card number (basic check for digits and length)
        $cardNumber = preg_replace('/\s+/', '', $_POST['card_number']);
        if (!ctype_digit($cardNumber) || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            FlashMessage::error('Please enter a valid credit card number.');
            header('Location: /credit-cards/' . $id . '/edit');
            exit();
        }
        
        $data = [
            'name' => $_POST['name'],
            'card_number_last4' => substr($cardNumber, -4),
            'expiry_month' => $_POST['expiry_month'],
            'expiry_year' => $_POST['expiry_year'],
        ];
        
        try {
            $this->creditCardModel->update($id, $data);
            FlashMessage::success('Credit card updated successfully!');
        } catch (Exception $e) {
            FlashMessage::error('Failed to update credit card. Please try again.');
        }
        
        header('Location: /credit-cards');
        exit();
    }

    public function delete($id) {
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /credit-cards');
            exit();
        }
        
        // Check if credit card exists
        $creditCard = $this->creditCardModel->find($id);
        if (!$creditCard) {
            FlashMessage::error('Credit card not found.');
            header('Location: /credit-cards');
            exit();
        }
        
        try {
            $this->creditCardModel->delete($id);
            FlashMessage::success('Credit card deleted successfully!');
        } catch (Exception $e) {
            FlashMessage::error('Failed to delete credit card. Please try again.');
        }
        
        header('Location: /credit-cards');
        exit();
    }
} 