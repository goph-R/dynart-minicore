<?php

namespace Dynart\Minicore;

class Helper {

    public function add($callerPath, $path) {
        $p = dirname($callerPath)."/".$path;
        if (!file_exists($p)) {
            throw new FrameworkException("File not found for include: ".$p);
        }
        include_once $p;
    }

}