<?php

namespace Shudd3r\Http\Tests\Doubles;


use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Records\Record;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\Registry;

class FakeRegistry implements Registry
{
    public function get($id) {
        return 'Hello World!';
    }

    public function has($id) {
        return true;
    }

    public function set(string $id, Record $value) {

    }

    public function container(): ContainerInterface {
        return $this;
    }

    public function entry(string $id): RegistryInput {
        return new RegistryInput($id, $this);
    }
}
