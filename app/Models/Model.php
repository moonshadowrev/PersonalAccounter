<?php

use Medoo\Medoo;

class Model {
    protected $db;
    protected $table;

    public function __construct(Medoo $db) {
        $this->db = $db;
    }

    public function getDB() {
        return $this->db;
    }

    public function getAll($columns = '*', $where = []) {
        return $this->db->select($this->table, $columns, $where);
    }

    public function find($id) {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }
    
    public function count($where = []) {
        return $this->db->count($this->table, $where);
    }

    public function sum($column, $where = []) {
        return $this->db->sum($this->table, $column, $where);
    }

    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->id();
    }
    
    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }
    
    public function getPaginated($page, $limit, $where = [], $order = ['id' => 'DESC']) {
        $offset = ($page - 1) * $limit;
        $where['LIMIT'] = [$offset, $limit];
        $where['ORDER'] = $order;
        return $this->db->select($this->table, '*', $where);
    }
} 