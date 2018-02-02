<?php

namespace Shudd3r\Http\Tests\Container;

use Shudd3r\Http\Src\Container\Exception\EntryNotFoundException;
use Shudd3r\Http\Src\Container\Exception\InvalidIdException;
use Shudd3r\Http\Src\Container\Registry\TreeRegistry;


class TreeContainerTest extends FlatContainerTest
{
    protected function registry(array $data = []) {
        return new TreeRegistry($data);
    }

    private function withTreeSettings() {
        $factory = $this->factory();
        $factory->addRecord('group.test')->value('Hello World!');
        $factory->addRecord('group.lazy.foo')->lazy(function () {
            return 'Lazy Foo';
        });
        $factory->addRecord('its.over')->value(9000);
        return $factory;
    }

    public function testGetArrayOfExtractedValues() {
        $container = $this->withTreeSettings()->container();
        $expected = [
            'test' => 'Hello World!',
            'lazy' => ['foo' => 'Lazy Foo']
        ];
        $this->assertSame($expected, $container->get('group'));
    }

    public function testHasConfiguredPath_ReturnsTrue() {
        $factory = $this->withTreeSettings();
        $factory->addRecord('test')->value(['subA' => 'value']);
        $container = $factory->container();

        $this->assertTrue($container->has('group.lazy'));
        $this->assertTrue($container->has('test.subA'));
    }

    public function testHasUnreachablePath_ReturnsFalse() {
        $factory = $this->withTreeSettings();
        $factory->addRecord('test')->value(['subA' => 'value']);
        $container = $factory->container();

        $this->assertFalse($container->has('group.notLazy'));
        $this->assertFalse($container->has('test.subB'));
    }

    public function testGetPartOfExtractedArray() {
        $factory = $this->factory();
        $factory->addRecord('array')->value(['keyA' => 'value', 'keyB' => ['first' => 10, 'second' => 11]]);
        $container = $factory->container();

        $this->assertSame('value', $container->get('array.keyA'));
        $this->assertSame(['first' => 10, 'second' => 11], $container->get('array.keyB'));
        $this->assertSame(11, $container->get('array.keyB.second'));
    }

    public function testHasUnreachableExtractedArrayPath_ReturnsFalse() {
        $factory = $this->factory();
        $factory->addRecord('group')->value(['test' => ['not-used' => 'value']]);
        $container = $factory->container();

        $this->assertFalse($container->has('group.test.used'));
    }

    public function testGetUnreachableExtractedArrayPath_ThrowsException() {
        $factory = $this->factory();
        $factory->addRecord('group')->value(['test' => ['not-used' => 'value']]);
        $container = $factory->container();

        $this->expectException(EntryNotFoundException::class);
        $container->get('group.test.used');
    }

    public function testOverridingLeafNode_ThrowsException() {
        $factory = $this->factory();
        $factory->addRecord('not-group')->value(1);
        $this->expectException(InvalidIdException::class);
        $factory->addRecord('not-group.override')->value(2);
    }
}
