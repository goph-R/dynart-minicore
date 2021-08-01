<?php

namespace Dynart\Minicore;

abstract class Table {
    
    protected $db;
    protected $name = '';
    protected $autoId = true;
    protected $primaryKey = 'id';
    protected $fields = []; // [name => default value]

    public function __construct(string $database='database') {
        $framework = Framework::instance();
        $this->db = $framework->get($database);
    }

    public function save(Record $record) {
        if ($record->isNew()) {
            $this->insert($record);
        } else {
            $this->update($record);
        }
    }

    public function create() {
        $data = $this->fields; // copy the default data
        return new Record($data);
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
            list($condition, $conditionParams) = $this->getPrimaryKeyConditionAndParams($record);
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

    protected function getPrimaryKeys() {
        return is_string($this->primaryKey) ? [$this->primaryKey] : $this->primaryKey;
    }

    protected function getPrimaryKeyConditionAndParams(Record $record) {
        $conditions = [];
        $params = [];
        foreach ($this->getPrimaryKeys() as $primaryKey) {
            $conditions[] = $this->db->escapeName($primaryKey).' = :pk_'.$primaryKey;
            $params[':pk_'.$primaryKey] = $record->get($primaryKey);
        }
        return ['('.join(' AND ', $conditions).')', $params];
    }

}