<?php

namespace Shudd3r\Http\Tests\Doubles;

use Closure;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Factory;


class MockedContainerFactory implements Factory
{
    public $values = [];
    public $closures = [];

    public function container(): ContainerInterface {
        return new FakeContainer();
    }

    public function value($name, $value) {
        $this->values[$name] = $value;
    }

    public function lazy($name, Closure $closure) {
        $this->closures[$name] = $closure;
    }
}
