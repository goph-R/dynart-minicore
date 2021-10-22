<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;
use Dynart\Minicore\FrameworkException;

abstract class Query {
    
    protected $framework;
    protected $db;
    protected $sqlParams = [];
    protected $fieldsForOrdering = [];
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

    public function findAll($fields=null, array $options=[]) {
        $sql = $this->getSelect($fields, $options);
        $sql .= $this->getWhere();
        $sql .= $this->getOrder();
        $sql .= $this->getLimit();
        return $this->db->fetchAll($sql, $this->sqlParams);
    }

    public function findAllCount(array $options=[]) {
        $sql = $this->getSelect([['COUNT(1)']], $options);
        $sql .= $this->getWhere();
        return (int)$this->db->fetchColumn($sql, $this->sqlParams);        
    }    

    protected function getSelect($fields, array $options) {
        if ($fields === null) {
            // if no fields was given, set all for query
            $fields = $this->getAllFields();
        }
        $this->clearSqlParams();
        $this->setOptions($options);
        $this->useTranslatedInDefault();
        $this->setFieldsForOrdering($fields);
        return $this->getSelectSql($fields);
    }
    
    protected function setOptions(array $options) {
        $this->options = $options;
    }

    protected function useTranslatedInDefault() {
        if (!isset($this->options['use_translated'])) {
            $this->options['use_translated'] = true;
        }
    }

    protected function setFieldsForOrdering(array $fields) {
        $this->fieldsForOrdering = $this->getFieldsForOrdering($fields);
    }

    protected function getFieldsForOrdering(array $fields) {
        $result = [];
        foreach ($fields as $alias => $full) {
            if (is_integer($alias)) {
                $result[] = $full;
            } else {
                $result[] = $alias;
            }
        }
        return $result;
    }

    protected function getAsNames(array $fields) {
        $result = [];
        foreach ($fields as $alias => $full) {
            $full = is_array($full) ? $full[0] : $this->db->escapeName($full);
            if (is_integer($alias)) {
                $result[] = $full;
            } else {
                $result[] = $full.' AS '.$this->db->escapeName($alias);
            }
        }
        return $result;
    }

    protected function getAsNamesString(array $fields) {
        return join(', ', $this->getAsNames($fields));
    }

    protected function getSelectSql(array $fields) {
        $asNames = $this->getAsNamesString($fields);
        $table = $this->getTable();
        $tableName = $this->db->escapeName($table->getName());
        $sql = "SELECT $asNames FROM $tableName";
        $sql .= $this->createJoins();
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
            $result[$field] = $table->getName().'.'.$field;
        }
        if ($this->useTranslated()) {
            /** @var Table $trTable */
            $trTable = $table->getTranslationTable();
            $trFields = array_diff($trTable->getFields(), $trTable->getPrimaryKey());
            foreach ($trFields as $trField) {
                $result[$trField] = $trTable->getName().'.'.$trField;
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
        /** @var Table $trTable */
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
        if (!in_array($field, $this->fieldsForOrdering)) {
            return '';
        }
        $direction = $this->options['order_dir'] == 'asc' ? 'asc' : 'desc';
        return ' ORDER BY '.$field.' '.$direction;
    }
    
    protected function getLimit() {
        if (!isset($this->options['page']) || !isset($this->options['page_size'])) {
            return ' LIMIT 1';
        }
        $page = (int)$this->options['page'];
        if ($page < 0) {
            $page = 0;
        }
        $pageSize = (int)$this->options['page_size'];
        if ($pageSize < 1 || $pageSize > 100) { // TODO: max page size, default page size
            $pageSize = 1;
        }            
        $start = $page * $pageSize;
        return ' LIMIT '.$start.', '.$pageSize;
    }
}