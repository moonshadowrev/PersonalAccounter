<?php

require_once __DIR__ . '/../Migration.php';

class CreateExpensesTable extends Migration
{
    public function getName()
    {
        return '010_create_expenses_table';
    }
    
    public function up()
    {
        $this->createTable('expenses', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->integer('category_id')->nullable()->index('category_id');
            
            // Payment method references (only one should be set)
            $table->integer('credit_card_id')->nullable()->index('credit_card_id');
            $table->integer('bank_account_id')->nullable()->index('bank_account_id');
            $table->integer('crypto_wallet_id')->nullable()->index('crypto_wallet_id');
            
            $table->string('title'); // Main title/description
            $table->text('description')->nullable(); // Additional details
            $table->decimal('amount', 12, 2); // Amount with 2 decimal places
            $table->string('currency', 3)->default('USD');
            
            // Tax information (optional)
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable(); // Percentage rate
            $table->string('tax_type', 50)->nullable(); // VAT, Sales Tax, etc.
            
            // Date and reference information
            $table->date('expense_date'); // When the expense occurred
            $table->string('receipt_number')->nullable(); // Receipt or invoice number
            $table->string('vendor')->nullable(); // Who was paid
            $table->text('notes')->nullable(); // Additional notes
            
            // Status and processing
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->string('payment_method_type')->nullable(); // 'credit_card', 'bank_account', 'crypto_wallet', 'cash'
            
            // File attachments (JSON array of file paths)
            $table->text('attachments')->nullable(); // JSON array of attachment file paths
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
            $table->foreign('category_id', 'categories', 'id', 'SET NULL');
            $table->foreign('credit_card_id', 'credit_cards', 'id', 'SET NULL');
            $table->foreign('bank_account_id', 'bank_accounts', 'id', 'SET NULL');
            $table->foreign('crypto_wallet_id', 'crypto_wallets', 'id', 'SET NULL');
        });
        
        // Create expense_tags pivot table for many-to-many relationship
        $this->createTable('expense_tags', function($table) {
            $table->id();
            $table->integer('expense_id')->index('expense_id');
            $table->integer('tag_id')->index('tag_id');
            $table->foreign('expense_id', 'expenses', 'id', 'CASCADE');
            $table->foreign('tag_id', 'tags', 'id', 'CASCADE');
        });
        
        // Add composite unique constraint to prevent duplicate tag assignments
        $this->execute("ALTER TABLE `expense_tags` ADD UNIQUE KEY `expense_tag_unique` (`expense_id`, `tag_id`)");
    }
    
    public function down()
    {
        $this->dropTable('expense_tags');
        $this->dropTable('expenses');
    }
} 