<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;

abstract class Query {
    
    protected $framework;
    protected $db;
    protected $sqlParams = [];
    protected $selectFields = [];
    protected $options = [];

    protected $table;
    protected $textSearchFields = [];

    public function __construct(string $database='database') {
        $this->framework = Framework::instance();
        $this->db = $this->framework->get($database);
    }

    public function findById($id, $translated=true) {
        $sql = $this->getSelect(null, [
            'find_id' => $id,
            'use_translated' => $translated,
        ]);
        $sql .= $this->getWhere();
        $sql .= ' LIMIT 1';
        return $this->db->fetch($sql, $this->sqlParams);
    }

    public function findAll(array $options=[]) {
        $sql = $this->getSelect(null, $options);
        $sql .= $this->getWhere();
        $sql .= $this->getOrder();
        $sql .= $this->getLimit();
        return $this->db->fetchAll($sql, $this->sqlParams);
    }

    public function findAllCount(array $options=[]) {
        $sql = $this->getSelect(['COUNT(1)'], $options);
        $sql .= $this->getWhere();
        return $this->db->fetchColumn($sql, $this->sqlParams);        
    }    

    protected function getSelect($fields, array $options) {

        $this->clearSqlParams();
        $this->options = $options;
        
        // use translated in default
        if (!isset($this->options['use_translated'])) {
            $this->options['use_translated'] = true;
        }
        
        // use all the fields in default
        $table = $this->getTable();
        if ($fields === null) {
            $this->selectFields = $this->getAllFields($this->options);
            $fieldNames = join(', ', $this->escapeNames($this->selectFields));
        } else {
            $this->selectFields = $fields;
            $fieldNames = join(', ', $this->selectFields);
        }

        // create the select        
        $tableName = $this->db->escapeName($table->getName());
        $sql = "SELECT $fieldNames FROM $tableName";
        $sql .= $this->createJoins($this->options);

        return $sql;
    }

    protected function getTable() {
        return $this->framework->get($this->table);
    }

    protected function clearSqlParams() {
        $this->sqlParams = [];
    }

    protected function addSqlParams(array $params) {
        $this->sqlParams = array_merge($this->sqlParams, $params);
    }

    protected function escapeNames(array $names) {
        $result = [];
        foreach ($names as $name) {
            $result[] = $this->db->escapeName($name);
        }
        return $result;
    }    

    protected function createJoins() {
        $joins = $this->getJoins();
        if (!$joins) {
            return '';
        }
        $result = '';
        foreach ($joins as $join) {
            if (is_array($join)) {
                $result .= ' '.$join['type'].' JOIN '.$join['condition'];
            } else {
                $result .= ' JOIN '.$join;
            }                
        }
        return $result;
    }

    protected function getJoins() {
        $result = [];
        if ($this->useTranslated()) {
            $result[] = $this->getTranslationJoin();
        }
        return $result;
    }

    protected function getWhere() {
        $conditions = $this->getConditions();
        return $conditions ? ' WHERE '.join(' AND ', $conditions) : '';
    }    

    protected function getConditions() {
        $result = [];
        if (isset($this->options['find_id'])) {
            $table = $this->getTable();
            list($condition, $params) = $table->getPrimaryKeyConditionAndParams($this->options['find_id']);
            $this->addSqlParams($params);
            $result[] = $condition;
        }
        $condition = $this->getTextSearchCondition();
        if ($condition) {
            $result[] = $condition;
        }
        return $result;
    }    

    protected function getAllFields() {
        $result = [];
        $table = $this->getTable();
        $fields = $table->getFields();
        foreach ($fields as $field) {
            $result[] = $table->getName().'.'.$field;
        }
        if ($this->useTranslated()) {
            $trTable = $table->getTranslationTable();
            $trFields = array_diff($trTable->getFields(), $trTable->getPrimaryKey());
            foreach ($trFields as $trField) {
                $result[] = $trTable->getName().'.'.$trField;
            }
        }
        return $result;
    }    

    protected function getTranslationJoin() {
        $table = $this->getTable();
        if (is_array($table->getPrimaryKey())) {
            throw new FrameworkException("Primary key must be single!");
        }
        $primaryKey = $this->db->escapeName($table->getName().'.'.$table->getPrimaryKey());
        $trTable = $table->getTranslationTable();
        $trTableName = $this->db->escapeName($trTable->getName());
        $trPrimaryKeys = $trTable->getPrimaryKey();
        $trPrimaryKey = $this->db->escapeName($trTable->getName().'.'.$trPrimaryKeys[0]);
        $trLocale = $this->db->escapeName($trTable->getName().'.'.$trPrimaryKeys[1]);
        $translation = $this->framework->get('translation');
        $this->addSqlParams([':tr_locale' => $translation->getLocale()]);
        return "$trTableName ON $trPrimaryKey = $primaryKey AND $trLocale = :tr_locale";
    }

    protected function useTranslated() {
        $table = $this->getTable();
        return $table->hasTranslationTable()
            && isset($this->options['use_translated'])
            && $this->options['use_translated'];
    }

    protected function getTextSearchCondition() {
        if (!$this->textSearchFields || !isset($this->options['text']) || !$this->options['text']) {
            return '';
        }
        $likeText = '%'.str_replace('%', '\%', $this->options['text']).'%';
        $conditions = [];
        $params = [];
        foreach ($this->textSearchFields as $field) {
            $conditions[] = $this->db->escapeName($field).' LIKE :'.$field;
            $params[':'.$field] = $likeText;
        }
        $this->addSqlParams($params);
        return '('.join(' OR ', $conditions).')';
    }

    protected function getOrder() {
        if (!isset($this->options['order_by']) || !isset($this->options['order_dir'])) {
            return '';
        }
        $field = $this->options['order_by'];
        $table = $this->getTable();
        if (!in_array($field, $this->selectFields)) {
            return '';
        }
        $direction = $this->options['order_dir'] == 'asc' ? 'asc' : 'desc';
        return ' ORDER BY '.$field.' '.$direction;
    }
    
    protected function getLimit() {
        if (!isset($this->options['page']) || !isset($this->options['page_size'])) {
            return '';
        }
        $page = (int)$this->options['page'];
        if ($page < 0) {
            $page = 0;
        }
        $pageSize = (int)$this->options['page_size'];
        if ($pageSize < 1 || $pageSize > 100) { // TODO: max page size, default page size
            $pageSize = 25;
        }            
        $start = $page * $pageSize;
        return ' LIMIT '.$start.', '.$pageSize;
    }
}