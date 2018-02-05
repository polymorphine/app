<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;


interface Record
{
    public function value(ContainerInterface $c);
}
