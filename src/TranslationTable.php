<?php

namespace Dynart\Minicore;

abstract class TranslationTable extends Table {

    protected $primaryKey = ['id', 'locale'];
    protected $autoId = false;

}