<?php

namespace Dynart\Minicore;

class RouteAliases {

    public function hasAlias($alias) {
        return false;
    }

    public function getPath($alias) {
        return null;
    }

    public function hasPath($path) {
        return false;
    }

    public function getAlias($path) {
        return null;
    }

}