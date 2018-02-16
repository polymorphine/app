<?php

namespace Polymorphine\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Polymorphine\Container;
use Closure;


class MockedContainerFactory implements Container\Factory
{
    public $container = [];

    public function container(): ContainerInterface {
        return new FakeContainer();
    }

    public function value($name, $value): void {
        $this->set($name, $value);
    }

    public function lazy($name, Closure $closure): void {
        $this->set($name, $closure);
    }

    public function record($name, Container\Record $record): void {
        $this->set($name, $record);
    }

    private function set($name, $value) {
        $this->container[$name] = $value;
    }
}
