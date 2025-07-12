<?php

abstract class Migration
{
    protected $database;
    
    public function __construct($database)
    {
        $this->database = $database;
    }
    
    /**
     * Run the migration
     */
    abstract public function up();
    
    /**
     * Reverse the migration
     */
    abstract public function down();
    
    /**
     * Get migration name/identifier
     */
    abstract public function getName();
    
    /**
     * Execute raw SQL
     */
    protected function execute($sql)
    {
        return $this->database->query($sql);
    }
    
    /**
     * Create a table
     */
    protected function createTable($tableName, $callback)
    {
        $schema = new TableSchema($tableName);
        $callback($schema);
        
        $sql = $schema->toSql();
        return $this->execute($sql);
    }
    
    /**
     * Drop a table safely
     */
    protected function dropTable($tableName)
    {
        // First drop foreign key constraints that reference this table
        $this->dropForeignKeysReferencingTable($tableName);
        
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        return $this->execute($sql);
    }
    
    /**
     * Drop foreign keys that reference a specific table
     */
    protected function dropForeignKeysReferencingTable($tableName)
    {
        try {
            // Get all foreign keys that reference this table
            $result = $this->database->query("
                SELECT 
                    TABLE_NAME,
                    CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
                AND REFERENCED_TABLE_NAME = '{$tableName}'
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            if ($result) {
                $foreignKeys = $result->fetchAll(PDO::FETCH_ASSOC);
                foreach ($foreignKeys as $fk) {
                    $this->execute("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
                }
            }
        } catch (Exception $e) {
            // Ignore errors when dropping foreign keys - they might not exist
        }
    }
    
    /**
     * Check if table exists
     */
    protected function tableExists($tableName)
    {
        try {
            $result = $this->database->query("SHOW TABLES LIKE '{$tableName}'");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if foreign key constraint exists
     */
    protected function constraintExists($tableName, $constraintName)
    {
        try {
            $result = $this->database->query("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$tableName}' 
                AND CONSTRAINT_NAME = '{$constraintName}'
            ");
            
            if ($result) {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                return $row['count'] > 0;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

class TableSchema
{
    private $tableName;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    private $engine = 'InnoDB';
    private $charset = 'utf8mb4';
    
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }
    
    public function id($name = 'id')
    {
        $this->columns[] = "`{$name}` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    public function string($name, $length = 255)
    {
        $this->columns[] = "`{$name}` varchar({$length}) NOT NULL";
        return $this;
    }
    
    public function text($name)
    {
        $this->columns[] = "`{$name}` text";
        return $this;
    }
    
    public function integer($name)
    {
        $this->columns[] = "`{$name}` int(11) NOT NULL";
        return $this;
    }
    
    public function decimal($name, $precision = 10, $scale = 2)
    {
        $this->columns[] = "`{$name}` decimal({$precision},{$scale}) NOT NULL";
        return $this;
    }
    
    public function enum($name, $values)
    {
        $enumValues = "'" . implode("','", $values) . "'";
        $this->columns[] = "`{$name}` enum({$enumValues}) NOT NULL";
        return $this;
    }
    
    public function date($name)
    {
        $this->columns[] = "`{$name}` date DEFAULT NULL";
        return $this;
    }
    
    public function timestamps()
    {
        $this->columns[] = "`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }
    
    public function timestamp($name)
    {
        $this->columns[] = "`{$name}` timestamp NULL DEFAULT NULL";
        return $this;
    }
    
    public function nullable()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            $this->columns[$lastIndex] = str_replace(' NOT NULL', '', $this->columns[$lastIndex]);
        }
        return $this;
    }
    
    public function default($value)
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            if (is_string($value)) {
                $this->columns[$lastIndex] .= " DEFAULT '{$value}'";
            } else {
                $this->columns[$lastIndex] .= " DEFAULT {$value}";
            }
        }
        return $this;
    }
    
    public function unique($column)
    {
        $this->indexes[] = "UNIQUE KEY `{$column}` (`{$column}`)";
        return $this;
    }
    
    public function index($column, $name = null)
    {
        $indexName = $name ?: $column;
        $this->indexes[] = "KEY `{$indexName}` (`{$column}`)";
        return $this;
    }
    
    public function foreign($column, $referencedTable, $referencedColumn = 'id', $onDelete = 'CASCADE')
    {
        $constraintName = $this->tableName . '_' . $column . '_foreign';
        $this->foreignKeys[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}` (`{$referencedColumn}`) ON DELETE {$onDelete}";
        return $this;
    }
    
    public function toSql()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (\n";
        
        // Add columns
        $parts = array_merge($this->columns, $this->indexes, $this->foreignKeys);
        $sql .= "  " . implode(",\n  ", $parts) . "\n";
        
        $sql .= ") ENGINE={$this->engine} DEFAULT CHARSET={$this->charset};";
        
        return $sql;
    }
} 