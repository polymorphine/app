<?php

namespace Shudd3r\Http\Src\Container\Records;

use Shudd3r\Http\Src\Container\Container;
use Closure;


class LazyRecord implements Record
{
    private $value;
    private $callback;
    private $container;

    public function __construct(Closure $callback, Container $container) {
        $this->callback = $callback;
        $this->container = $container;
    }

    public function value() {
        return isset($this->value) ? $this->value : $this->value = $this->invoke();
    }

    private function invoke() {
        $callback = $this->callback->bindTo($this->container, $this->container);
        return $callback();
    }
}