<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;


class Container implements ContainerInterface
{
    private $registry;

    public function __construct(ContainerInterface $registry) {
        $this->registry = $registry;
    }

    public function has($id): bool {
        return $this->registry->has($id);
    }

    public function get($id) {
        return $this->registry->get($id);
    }
}
