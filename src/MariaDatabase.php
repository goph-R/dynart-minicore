<?php

namespace Dynart\Minicore;

class MariaDatabase extends Database {
    
    public function escapeName(string $name) {
        // TODO: regex check
        // TODO: split by dot
        return "`$name`";
    }

    protected function connect() {
        if (!$this->connected) {
            parent::connect();
            $this->query("USE ".$this->config->get('database.'.$this->name.'.name'));
            $this->query("SET NAMES 'utf8'");
        }
    }
}