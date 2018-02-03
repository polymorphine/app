<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;


class FakeContainer implements ContainerInterface
{
    public function get($id) {
        return 'Hello World!';
    }

    public function has($id) {
        return true;
    }
}
