<?php

namespace Shudd3r\Http\Tests\Doubles;

use Closure;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Factory;
use Shudd3r\Http\Src\Container\Record;


class MockedContainerFactory implements Factory
{
    public $container = [];

    public function container(): ContainerInterface {
        return new FakeContainer();
    }

    public function value($name, $value) {
        $this->set($name, $value);
    }

    public function lazy($name, Closure $closure) {
        $this->set($name, $closure);
    }

    public function record($name, Record $record) {
        $this->set($name, $record);
    }

    private function set($name, $value) {
        $this->container[$name] = $value;
    }
}
