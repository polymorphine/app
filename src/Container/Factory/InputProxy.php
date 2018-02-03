<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Closure;


class InputProxy
{
    private $name;
    private $factory;

    public function __construct(string $name, ContainerFactory $factory) {
        $this->name     = $name;
        $this->factory = $factory;
    }

    public function value($value) {
        $this->factory->value($this->name, $value);
    }

    public function lazy(Closure $closure) {
        $this->factory->lazy($this->name, $closure);
    }
}
