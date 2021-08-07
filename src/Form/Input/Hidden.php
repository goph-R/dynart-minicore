<?php 

namespace Dynart\Minicore\Form\Input;

use Dynart\Minicore\Form\Input\Text;

class Hidden extends Text {

    protected $type = 'hidden';
    protected $hidden = true;

}
