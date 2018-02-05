<?php

namespace Shudd3r\Http\Src\Container\Record;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Record;


class DirectRecord implements Record
{
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function value(ContainerInterface $c) {
        return $this->value;
    }
}
