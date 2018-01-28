<?php

namespace Shudd3r\Http\Src\Container\Entry;

use Shudd3r\Http\Src\Container\Entry;


class DirectEntry implements Entry
{
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function value() {
        return $this->value;
    }
}
