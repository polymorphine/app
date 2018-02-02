<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;


interface Registry extends ContainerInterface
{
    public function get($id);
    public function has($id);
    public function set(string $id, Record $value);
}
