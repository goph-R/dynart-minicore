<?php

namespace Dynart\Minicore;

class MariaDatabase extends Database {
    
    public function escapeName(string $name) {
        // TODO: regex check
        $parts = explode('.', $name);
        return '`'.join('`.`', $parts).'`';
    }

    protected function connect() {
        if (!$this->connected) {
            parent::connect();
            $this->query("USE ".$this->config->get('database.'.$this->name.'.name'));
            $this->query("SET NAMES 'utf8'");
        }
    }
}