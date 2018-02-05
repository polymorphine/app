<?php

namespace Shudd3r\Http\Src\Container\Record;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Record;
use Closure;


class LazyRecord implements Record
{
    private $value;
    private $callback;

    public function __construct(Closure $callback) {
        $this->callback = $callback;
    }

    public function value(ContainerInterface $c) {
        return isset($this->value) ? $this->value : $this->value = $this->callback->__invoke($c);
    }
}
