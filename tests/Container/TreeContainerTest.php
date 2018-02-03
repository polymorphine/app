<?php

namespace Shudd3r\Http\Tests\Container;

use Shudd3r\Http\Src\Container\Factory\TreeContainerFactory;
use Shudd3r\Http\Src\Container\TreeContainer;
use Shudd3r\Http\Src\Container\Exception;


class TreeContainerTest extends FlatContainerTest
{
    protected function factory(array $data = []) {
        return new TreeContainerFactory($data);
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

        $this->expectException(Exception\EntryNotFoundException::class);
        $container->get('group.test.used');
    }

    public function testOverridingLeafNode_ThrowsException() {
        $factory = $this->factory();
        $factory->value('not-group', 1);
        $this->expectException(Exception\InvalidIdException::class);
        $factory->value('not-group.override', 2);
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer() {
        $construct = $this->registryConstructorParams();

        $expected = [
            'test' => 'Hello World!',
            'array' => [1,2,3],
            'assoc' => ['first' => 1, 'second' => 2],
            'category' => ['first' => 'one', 'second' => 'two'],
            'category.first' => 'one',
            'category.second' => 'two',
            'lazy' => ['hello' => 'Hello World!', 'goodbye' => 'see ya!'],
            'lazy.hello' => 'Hello World!',
            'lazy.goodbye' => 'see ya!',
            'assoc.first' => 1,
            'assoc.second' => 2,
            'callbacks' => ['one' => 'first', 'two' => 'second'],
            'callbacks.one' => 'first',
            'callbacks.two' => 'second'
        ];

        $container = $this->factory($construct)->container();

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    protected function registryConstructorParams() {
        return [
            'value' => [
                'test' => 'Hello World!',
                'category' => [
                    'first' => 'one',
                    'second' => 'two'
                ],
                'array' => [1,2,3],
                'assoc' => ['first' => 1, 'second' => 2]
            ],
            'lazy' => [
                'lazy' => [
                    'hello' => function () { return $this->get('test'); },
                    'goodbye' => function () { return 'see ya!'; }
                ],
                'callbacks' => [
                    'one' => function () { return 'first'; },
                    'two' => function () { return 'second'; }
                ]
            ]
        ];
    }

    public function testRegistryConstructWithPathArrayKeys_ThrowsException() {
        $invalidKey = 'not' . TreeContainer::PATH_SEPARATOR . 'ok';
        $this->expectException(Exception\InvalidStateException::class);
        $this->factory(['value' => ['first' => 'ok', $invalidKey => [1, 2]]])->container();
    }
}
