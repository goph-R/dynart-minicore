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
        $table = $this->getTable();
        list($condition, $params) = $table->getPrimaryKeyConditionAndParams($id);
        $this->addSqlParams($params);
        $fieldNames = join(', ', $this->escapeNames($this->getAllFields($translated)));
        $name = $this->db->escapeName($table->getName());
        $sql = "SELECT $fieldNames FROM $name";
        if ($translated && $table->hasTranslationTable()) {
            $translationJoin = $this->getTranslationJoin();
            $sql .= " JOIN $translationJoin";
        }
        $sql .= " WHERE $condition";
        $sql .= " LIMIT 1"; // TODO: only on databases that support the limit clause
        return $this->db->fetch($sql, $this->sqlParams);
    }

    public function findAll(array $filter=[]) {
        $this->clearSqlParams();
        $sql = $this->getSelect(null, $filter);
        $sql .= $this->getWhere($filter);
        $sql .= $this->getOrder($filter);
        $sql .= $this->getLimit($filter);
        return $this->db->fetchAll($sql, $this->sqlParams);
    }

    public function findAllCount(array $filter) {
        $this->clearSqlParams();
        $sql = $this->getSelect('COUNT(1)', $filter);
        $sql .= $this->getWhere($filter);
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

    public function getSelect($fields=null, array $filter=[]) {
        $table = $this->getTable();
        if ($fields === null) {
            $this->selectFields = $this->getAllFields(true);
        } else {
            $this->selectFields = $fields;
        }
        $fieldNames = join(', ', $this->escapeNames($this->selectFields));
        $tableName = $this->db->escapeName($table->getName());
        $sql = "SELECT $fieldNames FROM $tableName";
        if ($table->hasTranslationTable()) {
            $translationJoin = $this->getTranslationJoin();
            $sql .= " JOIN $translationJoin";
        }
        return $sql;
    }

    protected function getTextSearchCondition(array &$filter) {
        if (!$this->textSearchFields || !isset($filter['text']) || !$filter['text']) {
            return '';
        }
        $likeText = '%'.str_replace('%', '\%', $filter['text']).'%';
        $conditions = [];
        $params = [];
        foreach ($this->textSearchFields as $field) {
            $conditions[] = $this->db->escapeName($field).' LIKE :'.$field;
            $params[':'.$field] = $likeText;
        }
        $this->addSqlParams($params);
        return '('.join(' OR ', $conditions).')';
    }

    protected function getWhere(array &$filter) {
        $condition = $this->getTextSearchCondition($filter);
        return $condition ? ' WHERE '.$condition : '';
    }

    protected function getOrder(array &$filter) {
        if (!isset($filter['order_by']) || !isset($filter['order_dir'])) {
            return '';
        }
        $field = $filter['order_by'];
        $table = $this->getTable();
        if (!in_array($field, $this->selectFields)) {
            return '';
        }
        $direction = $filter['order_dir'] == 'asc' ? 'asc' : 'desc';
        return ' ORDER BY '.$field.' '.$direction;
    }
    
    protected function getLimit(array &$filter) {
        if (!isset($filter['page']) || !isset($filter['page_limit'])) {
            return '';
        }
        $page = (int)$filter['page'];
        if ($page < 0) {
            $page = 0;
        }
        $pageLimit = (int)$filter['page_limit'];
        if ($pageLimit < 1 || $pageLimit > 100) { // TODO: max page limit, default page limit
            $pageLimit = 25;
        }            
        $start = $page * $pageLimit;
        return ' LIMIT '.$start.', '.$pageLimit;
    }
}