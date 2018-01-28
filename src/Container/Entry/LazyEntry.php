<?php

namespace Shudd3r\Http\Src\Container\Entry;

use Shudd3r\Http\Src\Container\Entry;
use Shudd3r\Http\Src\Container\Registry;
use Closure;


class LazyEntry implements Entry
{
    private $value;
    private $callback;
    private $registry;

    public function __construct(Closure $callback, Registry $registry) {
        $this->callback = $callback;
        $this->registry = $registry;
    }

    public function value() {
        return isset($this->value) ? $this->value : $this->value = $this->invoke();
    }

    private function invoke() {
        $callback = $this->callback->bindTo($this->registry, $this->registry);
        return $callback();
    }
}
