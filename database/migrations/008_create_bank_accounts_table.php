<?php

require_once __DIR__ . '/../Migration.php';

class CreateBankAccountsTable extends Migration
{
    public function getName()
    {
        return '008_create_bank_accounts_table';
    }
    
    public function up()
    {
        $this->createTable('bank_accounts', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->string('name'); // User-friendly name like "Main Checking"
            $table->string('bank_name'); // Name of the bank
            $table->string('account_type')->default('checking'); // checking, savings, business, etc.
            $table->string('account_number_last4', 4); // Last 4 digits for display
            $table->string('routing_number', 50)->nullable(); // Bank routing number (updated for international support)
            $table->string('currency', 3)->default('USD');
            $table->string('country_code', 3)->nullable(); // Country code for international support
            $table->string('iban', 34)->nullable(); // IBAN for European banking
            $table->string('swift_bic', 11)->nullable(); // SWIFT/BIC code for international transfers
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
    }
    
    public function down()
    {
        $this->dropTable('bank_accounts');
    }
} 