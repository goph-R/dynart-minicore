<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;

abstract class Query {
    
    protected $framework;
    protected $db;
    protected $sqlParams = [];
    protected $selectFields = [];

    protected $table;
    protected $textSearchFields = [];

    public function __construct(string $database='database') {
        $this->framework = Framework::instance();
        $this->db = $this->framework->get($database);
    }

    public function findById($id, $translated=true) {
        $this->clearSqlParams();
        $options = [
            'find_id' => $id,
            'use_translated' => $translated,
        ];
        $sql = $this->getSelect(null, $options);
        $sql .= $this->getWhere($options);
        $sql .= ' LIMIT 1';
        return $this->db->fetch($sql, $this->sqlParams);
    }

    public function findAll(array $options=[]) {
        $this->clearSqlParams();
        $sql = $this->getSelect(null, $options);
        $sql .= $this->getWhere($options);
        $sql .= $this->getOrder($options);
        $sql .= $this->getLimit($options);
        return $this->db->fetchAll($sql, $this->sqlParams);
    }

    public function findAllCount(array $options) {
        $this->clearSqlParams();
        $sql = $this->getSelect('COUNT(1)', $options);
        $sql .= $this->getWhere($options);
        return $this->db->fetchColumn($sql, $this->sqlParams);        
    }    

    protected function getTable() {
        return $this->framework->get($this->table);
    }

    public function getAllFields(bool $translated) {
        $result = [];
        $table = $this->getTable();
        $fields = $table->getFields();
        foreach ($fields as $field) {
            $result[] = $table->getName().'.'.$field;
        }
        if ($translated && $table->hasTranslationTable()) {
            $trTable = $table->getTranslationTable();
            $trFields = array_diff($trTable->getFields(), $trTable->getPrimaryKey());
            foreach ($trFields as $trField) {
                $result[] = $trTable->getName().'.'.$trField;
            }
        }
        return $result;
    }

    protected function escapeNames(array $names) {
        $result = [];
        foreach ($names as $name) {
            $result[] = $this->db->escapeName($name);
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

    protected function clearSqlParams() {
        $this->sqlParams = [];
    }

    protected function addSqlParams(array $params) {
        $this->sqlParams = array_merge($this->sqlParams, $params);
    }

    public function getSelect($fields=null, array $options=[]) {
        
        // use translated in default
        if (!isset($options['use_translated'])) {
            $options['use_translated'] = true;
        }
        
        // use all the fields in default
        $table = $this->getTable();
        if ($fields === null) {
            $this->selectFields = $this->getAllFields(true);
        } else {
            $this->selectFields = $fields;
        }

        // create the select
        $fieldNames = join(', ', $this->escapeNames($this->selectFields));
        $tableName = $this->db->escapeName($table->getName());
        $sql = "SELECT $fieldNames FROM $tableName";

        // create the joins
        $joins = $this->getJoins($options);
        if ($joins) {
            foreach ($joins as $join) {
                $sql .= ' JOIN '.$join;
            }
        }
        return $sql;
    }

    protected function useTranslated(array &$options) {
        $table = $this->getTable();
        return $table->hasTranslationTable()
            && isset($options['use_translated'])
            && $options['use_translated'];
    }

    protected function getJoins(array &$options) {
        $result = [];
        if ($this->useTranslated($options)) {
            $result[] = $this->getTranslationJoin();
        }
        return $result;
    }

    protected function getWhere(array &$options) {
        $conditions = $this->getConditions($options);
        return $conditions ? ' WHERE '.join(' AND ', $conditions) : '';
    }

    protected function getConditions(array &$options) {
        $result = [];
        if (isset($options['find_id'])) {
            $table = $this->getTable();
            list($condition, $params) = $table->getPrimaryKeyConditionAndParams($options['find_id']);
            $this->addSqlParams($params);
            $result[] = $condition;
        }
        $condition = $this->getTextSearchCondition($options);
        if ($condition) {
            $result[] = $condition;
        }
        return $result;
    }

    protected function getTextSearchCondition(array &$options) {
        if (!$this->textSearchFields || !isset($options['text']) || !$options['text']) {
            return '';
        }
        $likeText = '%'.str_replace('%', '\%', $options['text']).'%';
        $conditions = [];
        $params = [];
        foreach ($this->textSearchFields as $field) {
            $conditions[] = $this->db->escapeName($field).' LIKE :'.$field;
            $params[':'.$field] = $likeText;
        }
        $this->addSqlParams($params);
        return '('.join(' OR ', $conditions).')';
    }

    protected function getOrder(array &$options) {
        if (!isset($options['order_by']) || !isset($options['order_dir'])) {
            return '';
        }
        $field = $options['order_by'];
        $table = $this->getTable();
        if (!in_array($field, $this->selectFields)) {
            return '';
        }
        $direction = $options['order_dir'] == 'asc' ? 'asc' : 'desc';
        return ' ORDER BY '.$field.' '.$direction;
    }
    
    protected function getLimit(array &$options) {
        if (!isset($options['page']) || !isset($options['page_limit'])) {
            return '';
        }
        $page = (int)$options['page'];
        if ($page < 0) {
            $page = 0;
        }
        $pageLimit = (int)$options['page_limit'];
        if ($pageLimit < 1 || $pageLimit > 100) { // TODO: max page limit, default page limit
            $pageLimit = 25;
        }            
        $start = $page * $pageLimit;
        return ' LIMIT '.$start.', '.$pageLimit;
    }
}