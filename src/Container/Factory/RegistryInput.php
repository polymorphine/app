<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Src\Container\Record;
use Shudd3r\Http\Src\Container\Records\DirectRecord;
use Shudd3r\Http\Src\Container\Records\LazyRecord;
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
        $this->push(new LazyRecord($closure, $this->registry));
    }

    private function push(Record $entry) {
        $this->registry->set($this->name, $entry);
    }
}
