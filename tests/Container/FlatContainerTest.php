<?php

namespace Shudd3r\Http\Tests\Container;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Container\Exception\InvalidIdException;
use Shudd3r\Http\Src\Container\Exception\InvalidStateException;
use Shudd3r\Http\Src\Container\Factory\FlatContainerFactory;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class FlatContainerTest extends TestCase
{
    protected function factory(array $data = []) {
        return new FlatContainerFactory($data);
    }

    protected function withBasicSettings() {
        $factory = $this->factory();
        $factory->value('test', 'Hello World!');
        $factory->lazy('lazy', function () { return 'Lazy Foo'; });

        return $factory;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ContainerInterface::class, $this->factory()->container());
    }

    public function testConfiguredRecordsAreAvailableFromContainer() {
        $container = $this->withBasicSettings()->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContaine() {
        $factory = $this->withBasicSettings();
        $factory->lazy('bar', function () {
            return substr($this->get('test'), 0, 6) . $this->get('lazy') . '!';
        });
        $container = $factory->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testInvalidContainerIdType_ThrowsException() {
        $container = $this->factory()->container();
        $this->expectException(ContainerExceptionInterface::class);
        $container->has(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException() {
        $container = $this->factory()->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer() {
        $construct = $this->registryConstructorParams();

        $expected = array_merge($construct['value'], $construct['lazy'], [
            'lazy.hello' => 'Hello World!',
            'lazy.goodbye' => 'see ya!'
        ]);

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
                'category.first' => 'one',
                'category.second' => 'two',
                'array' => [1,2,3],
                'assoc' => ['first' => 1, 'second' => 2],
                'callbacks' => [
                    'one' => function () { return 'first'; },
                    'two' => function () { return 'second'; }
                ]
            ],
            'lazy' => [
                'lazy.hello' => function () { return $this->get('test'); },
                'lazy.goodbye' => function () { return 'see ya!'; }
            ]
        ];
    }

    public function testConstructWithNotAssociativeArray_ThrowsException() {
        $this->expectException(ContainerExceptionInterface::class);
        $this->factory(['value' => ['first' => 'ok', 2 => 'not ok']])->container();
    }

    public function testCallbacksCannotModifyRegistry() {
        $factory = $this->factory();
        $factory->lazy('lazyModifier', function () {
            $vars = get_object_vars($this);
            return isset($vars['values']) || isset($vars['callbacks']);
        });
        $this->assertFalse($factory->container()->get('lazyModifier'));
    }

    public function testOverwritingExistingKey_ThrowsException() {
        $factory = $this->factory(['value' => ['test' => 'foo']]);
        $this->expectException(InvalidIdException::class);
        $factory->value('test', 'bar');
    }

    public function testNumericId_ThrowsException() {
        $factory = $this->factory();
        $this->expectException(InvalidIdException::class);
        $factory->lazy('74', function () { return 'foo'; });
    }

    public function testEmptyFactoryId_ThrowsException() {
        $factory = $this->factory();
        $this->expectException(InvalidIdException::class);
        $factory->lazy('', function () { return 'foo'; });
    }

    public function testInvalidTypeForLazyValues_ThrowsException() {
        $callback = function () { return 'this is valid'; };
        $factory = $this->factory(['lazy' => ['key' => $callback, 'invalid' => 'not closure']]);
        $this->expectException(InvalidStateException::class);
        $factory->container();
    }

    public function testEmptyIdContainerCall_ThrowsException() {
        $container = $this->withBasicSettings()->container();
        $this->expectException(InvalidIdException::class);
        $container->has('');
    }
}
