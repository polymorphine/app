<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Registry\Record;


interface Registry extends ContainerInterface
{
    public function get($id);
    public function has($id);
    public function set(string $id, Record $value);
}
