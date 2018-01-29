<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Records\Record;
use Shudd3r\Http\Src\Container\Records\RegistryInput;


interface Registry extends ContainerInterface
{
    public function get($id);
    public function has($id);
    public function set(string $id, Record $value);
    public function container(): ContainerInterface;
    public function entry(string $id): RegistryInput;
}
