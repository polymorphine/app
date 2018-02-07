<?php

namespace Shudd3r\Http\Tests\Container\Doubles;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Record;


class FakeRecord implements Record
{
    public $id;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function value(ContainerInterface $c) {
        return $this->id;
    }
}
