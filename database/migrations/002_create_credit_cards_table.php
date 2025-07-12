<?php

require_once __DIR__ . '/../Migration.php';

class CreateCreditCardsTable extends Migration
{
    public function getName()
    {
        return '002_create_credit_cards_table';
    }
    
    public function up()
    {
        $this->createTable('credit_cards', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->string('name');
            $table->string('card_number_last4', 4);
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
    }
    
    public function down()
    {
        $this->dropTable('credit_cards');
    }
} 