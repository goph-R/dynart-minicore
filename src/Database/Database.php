<?php

namespace Dynart\Minicore\Database;

use Dynart\Minicore\Framework;
use Dynart\Minicore\FrameworkException;
use Dynart\Minicore\Logger;

abstract class Database {

    protected $name;

    /** @var Framework */
    protected $framework;

    /** @var Logger */
    protected $logger;

    /** @var Config */
    protected $config;

    /** @var PDO */
    protected $pdo = null;

    protected $connected = false;

    public function __construct(string $name) {
        $this->framework = Framework::instance();
        $this->name = $name;
        $this->logger = $this->framework->get('logger');
        $this->config = $this->framework->get('config');
    }

    protected function connect() {
        $dsn = $this->config->get('database.'.$this->name.'.dsn');
        $user = $this->config->get('database.'.$this->name.'.user');
        $password = $this->config->get('database.'.$this->name.'.password');
        $this->pdo = new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->connected = true;
    }

    public function escapeName(string $name) {
        return $name;
    }

    public function query(string $query, array $params=[]) {
        $this->connect();
        try {
            // we only want json_encode the parameters if the logger level is info
            if ($this->logger->getLevel() <= Logger::INFO) { 
                $this->logger->info("Executing query: \n$query".$this->getParametersString($params));
            }
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
        } catch (\RuntimeException $e) {
            $this->logger->error("SQL error:\n$query".$this->getParametersString($params));
            throw $e;
        }
        return $stmt;
    }

    protected function getParametersString(array $params) {
        return $params ? "\nParameters: ".json_encode($params) : '';
    }

    public function fetchArray($query, $params=[]) {
        $stmt = $this->query($query, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt = null;
        return $result;
    }

    public function fetchAllArray(string $query, array $params=[]) {
        $stmt = $this->query($query, $params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt = null;
        return $result;
    }

    public function fetchColumn(string $query, array $params=[], int $index=0) {
        $stmt = $this->query($query, $params);
        $result = $stmt->fetchColumn($index);
        $stmt = null;
        return $result;
    }
    
    public function fetch(string $query, array $params=[]) {
        $result = $this->fetchAll($query, $params);
        return isset($result[0]) ? $result[0] : null;
    }

    public function fetchAll(string $query, array $params=[]) {
        $result = [];
        $stmt = $this->query($query, $params);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $data) {
            $result[] = new Record($data, false);
        }
        $stmt = null;
        return $result;
    }

    public function lastInsertId($name=null) {
        return $this->pdo->lastInsertId($name);
    }

    public function insert(string $tableName, array $data) {
        $tableName = $this->escapeName($tableName);
        $params = [];
        $names = [];
        foreach ($data as $name => $value) {
            $names[] = $this->escapeName($name);
            $params[':'.$name] = $value;
        }
        $namesString = join(', ', $names);
        $paramsString = join(', ', array_keys($params));
        $sql = "INSERT INTO $tableName ($namesString) VALUES ($paramsString)";
        $this->query($sql, $params);
    }

    public function update(string $tableName, array $data, string $condition='', array $conditionParams=[]) {
        $tableName = $this->escapeName($tableName);
        $params = [];
        $pairs = [];
        foreach ($data as $name => $value) {
            $pairs[] = $this->escapeName($name).' = :'.$name;
            $params[':'.$name] = $value;
        }
        $params = array_merge($params, $conditionParams);
        $pairsString = join(', ', $pairs);
        $where = $condition ? ' WHERE '.$condition : '';
        $sql = "UPDATE $tableName SET $pairsString$where";
        $this->query($sql, $params);
    }
    
    public function getInConditionAndParams(array $values, $paramNamePrefix='in') {
        $params = [];
        $in = "";
        foreach ($values as $i => $item) {
            $key = ":".$paramNamePrefix.$i;
            $in .= "$key,";
            $params[$key] = $item;
        }
        $condition = rtrim($in, ",");
        return ['condition' => $condition, 'params' => $params];
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollBack() {
        return $this->pdo->rollBack();
    }

}