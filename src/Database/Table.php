<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;
use Dynart\Minicore\FrameworkException;

abstract class Table {
    
    protected $framework;
    protected $db;
    protected $name = '';
    protected $primaryKey = 'id'; // use array for multi primary keys
    protected $fields = []; // [name => default value]
    
    // only on single primary keys!
    protected $autoId = true;
    protected $translationTable = null;

    public function __construct(string $database='database') {
        $this->framework = Framework::instance();
        $this->db = $this->framework->get($database);
    }

    public function getName() {
        return $this->name;
    }

    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    public function create($newRecord=true) {
        $data = $this->fields; // copy the default data
        return new Record($data, $newRecord);
    }

    public function save(Record $record) {
        if ($record->isNew()) {
            $this->insert($record);
        } else {
            $this->update($record);
        }
    }

    public function getFields() {
        return array_keys($this->fields);
    }

    public function getAllEscapedFields($translated) {
        $fullNames = [];
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $fullNames[] = $this->db->escapeName($this->name.'.'.$field);
        }
        if ($translated && $this->translationTable) {
            $trTable = $this->framework->get($this->translationTable);
            $trFields = array_diff($trTable->getFields(), $trTable->getPrimaryKey());
            foreach ($trFields as $trField) {
                $fullNames[] = $this->db->escapeName($trTable->getName().'.'.$trField);
            }
        }
        return join(', ', $fullNames);
    }

    public function getTranslationJoin(array &$params, string $locale) {
        if (is_array($this->primaryKey)) {
            throw new FrameworkException("Primary key must be single!");
        }
        $primaryKey = $this->db->escapeName($this->name.'.'.$this->primaryKey);
        $trTable = $this->framework->get($this->translationTable);
        $trTableName = $this->db->escapeName($trTable->getName());
        $trPrimaryKeys = $trTable->getPrimaryKey();
        $trPrimaryKey = $this->db->escapeName($trTable->getName().'.'.$trPrimaryKeys[0]);
        $trLocale = $this->db->escapeName($trTable->getName().'.'.$trPrimaryKeys[1]);
        $params[':tr_locale'] = $locale;
        return "$trTableName on $trPrimaryKey = $primaryKey and $trLocale = :tr_locale";
    }

    public function findById($id, $translated=true) {
        list($condition, $params) = $this->getPrimaryKeyConditionAndParams($id);
        $allFields = $this->getAllEscapedFields($translated);        
        $sql = "select $allFields from ".$this->db->escapeName($this->name);        
        if ($translated && $this->translationTable) {
            $translation = $this->framework->get('translation');
            $translationJoin = $this->getTranslationJoin($params, $translation->getLocale());
            $sql .= " join $translationJoin";
        }
        $sql .= " where $condition";
        $sql .= " limit 1"; // TODO: only on databases that support the limit clause
        return $this->db->fetch($sql, $params);
    }

    public function deleteById($id) {
        list($condition, $params) = $this->getPrimaryKeyConditionAndParams($id);
        $sql = "delete from ".$this->db->escapeName($this->name);
        $sql .= " where $condition";
        $sql .= " limit 1"; // TODO: only on databases that support the limit clause
        return $this->db->query($sql, $params);        
    }

    protected function insert(Record $record) {
        $all = $record->getAll();
        $data = $this->getSaveData($all);
        $this->db->insert($this->name, $data);
        if ($this->autoId && is_string($this->primaryKey)) {
            $record->set($this->primaryKey, $this->db->lastInsertId());
        }
        $record->setNew(false);
    }

    protected function update(Record $record) {
        $modified = $record->getModified();
        $data = $this->getSaveData($modified);
        if ($data) {
            $primaryKeyValue = $this->getPrimaryKeyValue($record);
            list($condition, $conditionParams) = $this->getPrimaryKeyConditionAndParams($primaryKeyValue);
            $this->db->update($this->name, $data, $condition, $conditionParams);
        }
    }

    protected function getSaveData(array &$data) {
        $result = [];
        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $this->fields)) {
                throw new FrameworkException("Field '$field' doesn't exist in table '{$this->name}'.");
            }
            if ($this->autoId && $this->primaryKey == $field) {
                continue;
            }
            $result[$field] = $data[$field];
        }
        return $result;
    }

    protected function getPrimaryKeyValue(Record $record) {
        $result = null;
        if (is_string($this->primaryKey)) { // single primary key            
            $result = $record->get($this->primaryKey);
        } else if (is_array($this->primaryKey)) { // multi primary key
            $result = [];
            foreach ($this->primaryKey as $primaryKey) {
                $result[] = $record->get($primaryKey);
            }
        }
        return $result;
    }

    protected function getPrimaryKeyConditionAndParams($primaryKeyValue) {        
        if (is_array($this->primaryKey) != is_array($primaryKeyValue)) {
            throw new FrameworkException("The primary key type doesn't match with ".gettype($primaryKeyValue).".");
        }
        $conditions = [];
        $params = [];
        if (is_string($this->primaryKey)) { // single primary key
            $conditions[] = $this->db->escapeName($this->name.'.'.$this->primaryKey).' = :pk_'.$this->primaryKey;
            $params[':pk_'.$this->primaryKey] = $primaryKeyValue;        
        } else if (is_array($this->primaryKey)) { // multi primary key
            $index = 0;
            foreach ($this->primaryKey as $primaryKey) {
                $conditions[] = $this->db->escapeName($this->name.'.'.$primaryKey).' = :pk_'.$primaryKey;
                $params[':pk_'.$primaryKey] = $primaryKeyValue[$index];
                $index++;
            }            
        }
        return ['('.join(' AND ', $conditions).')', $params];
    }

}