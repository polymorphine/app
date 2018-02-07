<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Closure;


interface Factory
{
    public function container(): ContainerInterface;
    public function value($name, $value);
    public function lazy($name, Closure $closure);
    public function record($name, Record $record);
}
