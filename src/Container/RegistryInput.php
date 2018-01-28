<?php

namespace Shudd3r\Http\Src\Container;

use Closure;
use Shudd3r\Http\Src\Container\Entry\DirectEntry;
use Shudd3r\Http\Src\Container\Entry\LazyEntry;


class RegistryInput
{
    private $name;
    private $registry;

    public function __construct(string $name, Registry $registry) {
        $this->name     = $name;
        $this->registry = $registry;
    }

    public function value($value) {
        $this->push(new DirectEntry($value));
    }

    public function lazy(Closure $closure) {
        $this->push(new LazyEntry($closure, $this->registry));
    }

    private function push(Entry $entry) {
        $this->registry->set($this->name, $entry);
    }
}
