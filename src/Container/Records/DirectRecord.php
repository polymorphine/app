<?php

namespace Shudd3r\Http\Src\Container\Records;


class DirectRecord implements Record
{
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function value() {
        return $this->value;
    }
}
