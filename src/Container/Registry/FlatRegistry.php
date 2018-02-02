<?php

namespace Shudd3r\Http\Src\Container\Registry;

use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Src\Container\Container;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Record;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\Exception\EntryNotFoundException;
use Shudd3r\Http\Src\Container\Exception\InvalidIdException;


class FlatRegistry implements Registry
{
    private $entries = [];
    private $container;

    public function __construct(array $entries = []) {
        $this->entries = $entries;
        $this->container = new Container($this);
    }

    public function get($id) {
        if (!$this->has($id)) { throw new EntryNotFoundException(); }
        return $this->entries[$id]->value();
    }

    public function has($id): bool {
        if (!is_string($id)) { throw new InvalidIdException(); }
        return isset($this->entries[$id]);
    }

    public function set(string $id, Record $value) {
        $this->entries[$id] = $value;
    }

    public function container(): ContainerInterface {
        return $this->container;
    }

    public function entry(string $id): RegistryInput {
        return new RegistryInput($id, $this);
    }
}
