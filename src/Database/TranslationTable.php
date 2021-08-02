<?php

namespace Dynart\Minicore\Database;

abstract class TranslationTable extends Table {

    protected $primaryKey = ['id', 'locale'];
    protected $autoId = false;

}