<?php

require_once __DIR__ . '/../Migration.php';

class CreateTagsTable extends Migration
{
    public function getName()
    {
        return '007_create_tags_table';
    }
    
    public function up()
    {
        $this->createTable('tags', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#10B981'); // Hex color code
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
        
        // Add composite unique constraint manually
        $this->execute("ALTER TABLE `tags` ADD UNIQUE KEY `user_id_name` (`user_id`, `name`)");
    }
    
    public function down()
    {
        $this->dropTable('tags');
    }
} 