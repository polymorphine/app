<?php

namespace Shudd3r\Http\Tests\Container;

use Shudd3r\Http\Src\Container\Exception\InvalidIdException;
use Shudd3r\Http\Src\Container\TreeRegistry;


class TreeRegistryTest extends FlatRegistryTest
{
    protected function registry(array $data = []) {
        return new TreeRegistry($data);
    }

    private function withTreeSettings() {
        $registry = $this->registry();
        $registry->entry('group.test')->value('Hello World!');
        $registry->entry('group.lazy.foo')->lazy(function () {
            return 'Lazy Foo';
        });
        $registry->entry('its.over')->value(9000);
        return $registry;
    }

    public function testGetArrayOfExtractedValues() {
        $container = $this->withTreeSettings()->container();
        $expected = [
            'test' => 'Hello World!',
            'lazy' => ['foo' => 'Lazy Foo']
        ];
        $this->assertSame($expected, $container->get('group'));
    }

    public function testGetPartOfExtractedArray() {
        $registry = $this->registry();
        $registry->entry('array')->value(['keyA' => 'value', 'keyB' => ['first' => 10, 'second' => 11]]);
        $container = $registry->container();

        $this->assertSame('value', $container->get('array.keyA'));
        $this->assertSame(['first' => 10, 'second' => 11], $container->get('array.keyB'));
        $this->assertSame(11, $container->get('array.keyB.second'));
    }

    public function testOverridingLeafNode_ThrowsException() {
        $registry = $this->registry();
        $registry->entry('not-group')->value(1);
        $this->expectException(InvalidIdException::class);
        $registry->entry('not-group.override')->value(2);
    }
}
