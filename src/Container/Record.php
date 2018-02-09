<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;


interface Record
{
    /**
     * Unwraps value requested from container.
     *
     * Container instance is passed as parameter as returned value
     * may depend on other Container's entries.
     *
     * @param ContainerInterface $c
     * @return mixed unwrapped record value
     */
    public function value(ContainerInterface $c);
}
