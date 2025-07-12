<?php

require_once __DIR__ . '/../Migration.php';

class CreateCategoriesTable extends Migration
{
    public function getName()
    {
        return '006_create_categories_table';
    }
    
    public function up()
    {
        $this->createTable('categories', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Hex color code
            $table->string('icon', 50)->nullable(); // FontAwesome icon class
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
        
        // Add composite unique constraint manually
        $this->execute("ALTER TABLE `categories` ADD UNIQUE KEY `user_id_name` (`user_id`, `name`)");
    }
    
    public function down()
    {
        $this->dropTable('categories');
    }
} 