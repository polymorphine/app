<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Container;
use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Src\Container\Registry\Records;
use Closure;


class ContainerFactory
{
    private $registry;
    private $container;

    public function __construct(Registry $registry) {
        $this->registry = $registry;
        $this->container = new Container($registry);
    }

    public function container(): ContainerInterface {
        return $this->container;
    }

    public function value($name, $value) {
        $this->registry->set($name, new Records\DirectRecord($value));
    }

    public function lazy($name, Closure $closure) {
        $this->registry->set($name, new Records\LazyRecord($closure, $this->container));
    }
}
