<?php

namespace Shudd3r\Http\Tests\Doubles;

use Shudd3r\Http\Src\Container\Registry;


class FakeRegistry implements Registry
{
    public function get($id) {
        return 'Hello World!';
    }

    public function has($id) {
        return true;
    }

    public function set(string $id, Registry\Record $value) {

    }
}
