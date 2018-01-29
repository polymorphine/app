<?php

namespace Shudd3r\Http\Src\Container\Records;

use Shudd3r\Http\Src\Container\Registry;
use Closure;


class RegistryInput
{
    private $name;
    private $registry;

    public function __construct(string $name, Registry $registry) {
        $this->name     = $name;
        $this->registry = $registry;
    }

    public function value($value) {
        $this->push(new DirectRecord($value));
    }

    public function lazy(Closure $closure) {
        $this->push(new LazyRecord($closure, $this->registry->container()));
    }

    private function push(Record $entry) {
        $this->registry->set($this->name, $entry);
    }
}
