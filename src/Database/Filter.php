<?php

namespace Dynart\Minicore\Database;

class Filter {

    /** @var Query */
    protected $query;

    protected $fields = [];
    protected $options = [];

    public function __construct(Query $query) {
        $this->query = $query;
        $this->addOption('use_translated', [$this, 'useTranslated']);
    }

    public function addFields(array $fields) {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function getFields() {
        return $this->fields;
    }

    public function addOption(string $option, $callable) {
        $this->options[$option] = $callable;
    }

    public function getOptions() {
        return $options;
    }

}