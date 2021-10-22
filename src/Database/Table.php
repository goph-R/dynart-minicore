<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;
use Dynart\Minicore\FrameworkException;

abstract class Table {
    
    protected $framework;
    protected $db;
    protected $name = '';
    protected $fields = []; // [name => [default_value => ''] or null]

    /** @var string|array */
    protected $primaryKey = 'id'; // use array for multi primary keys

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

    public function hasTranslationTable() {
        return $this->translationTable ? true : false;
    }

    /**
     * @return Table
     */
    public function getTranslationTable() {
        return $this->framework->get($this->translationTable);
    }

    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    public function create($newRecord=true) {
        $data = [];
        foreach ($this->fields as $name => $options) {
            if (is_array($options) && array_key_exists('default_value', $options)) {
                $data[$name] = $options['default_value'];
            }
        }
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

    public function deleteById($id) {
        list($condition, $params) = $this->getPrimaryKeyConditionAndParams($id);
        $name = $this->db->escapeName($this->name);
        $sql = "DELETE FROM $name WHERE $condition";
        $sql .= " LIMIT 1"; // TODO: only on databases that support the limit clause
        return $this->db->query($sql, $params);        
    }

    protected function insert(Record $record) {
        $all = $record->asArray();
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

    public function getPrimaryKeyConditionAndParams($primaryKeyValue) {        
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