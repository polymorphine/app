<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Container;
use Shudd3r\Http\Src\Container\Registry;


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

    public function addRecord(string $name): RegistryInput {
        return new RegistryInput($name, $this->registry);
    }
}
