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
        $factory->value('group.test', 'Hello World!');
        $factory->lazy('group.lazy.foo', function () { return 'Lazy Foo'; });
        $factory->value('its.over', 9000);
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
        $factory->value('test', ['subA' => 'value']);
        $container = $factory->container();

        $this->assertTrue($container->has('group.lazy'));
        $this->assertTrue($container->has('test.subA'));
    }

    public function testHasUnreachablePath_ReturnsFalse() {
        $factory = $this->withTreeSettings();
        $factory->value('test', ['subA' => 'value']);
        $container = $factory->container();

        $this->assertFalse($container->has('group.notLazy'));
        $this->assertFalse($container->has('test.subB'));
    }

    public function testGetPartOfExtractedArray() {
        $factory = $this->factory();
        $factory->value('array', ['keyA' => 'value', 'keyB' => ['first' => 10, 'second' => 11]]);
        $container = $factory->container();

        $this->assertSame('value', $container->get('array.keyA'));
        $this->assertSame(['first' => 10, 'second' => 11], $container->get('array.keyB'));
        $this->assertSame(11, $container->get('array.keyB.second'));
    }

    public function testHasUnreachableExtractedArrayPath_ReturnsFalse() {
        $factory = $this->factory();
        $factory->value('group', ['test' => ['not-used' => 'value']]);
        $container = $factory->container();

        $this->assertFalse($container->has('group.test.used'));
    }

    public function testGetUnreachableExtractedArrayPath_ThrowsException() {
        $factory = $this->factory();
        $factory->value('group', ['test' => ['not-used' => 'value']]);
        $container = $factory->container();

        $this->expectException(EntryNotFoundException::class);
        $container->get('group.test.used');
    }

    public function testOverridingLeafNode_ThrowsException() {
        $factory = $this->factory();
        $factory->value('not-group', 1);
        $this->expectException(InvalidIdException::class);
        $factory->value('not-group.override', 2);
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer() {
        $construct = array_merge($this->registryConstructorParams(), [
            'lazy' => [
                'hello' => function () { return $this->get('test'); },
                'goodbye' => function () { return 'see ya!'; }
            ],
            'category' => [
                'first' => 'one',
                'second' => 'two'
            ],
        ]);

        unset($construct['lazy.hello'], $construct['lazy.goodbye'], $construct['category.first'], $construct['category.second']);

        $expected = array_merge($construct, [
            'lazy.hello' => 'Hello World!',
            'lazy.goodbye' => 'see ya!',
            'lazy' => ['hello' => 'Hello World!', 'goodbye' => 'see ya!'],
            'assoc.first' => 1,
            'assoc.second' => 2,
            'callbacks' => ['one' => 'first', 'two' => 'second'],
            'callbacks.one' => 'first',
            'callbacks.two' => 'second'
        ]);

        $registry  = $this->registry($construct);
        $container = $this->factory($registry)->container();

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testRegistryConstructWithPathArrayKeys_ThrowsException() {
        $invalidKey = 'not' . TreeRegistry::PATH_SEPARATOR . 'ok';
        $this->expectException(InvalidIdException::class);
        $this->registry(['first' => 'ok', 'second' => [$invalidKey => 1, 'not-relevant' => 'value']]);
    }
}
