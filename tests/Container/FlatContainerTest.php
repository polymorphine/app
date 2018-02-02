<?php

namespace Shudd3r\Http\Tests\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Factory\ContainerFactory;
use Shudd3r\Http\Src\Container\Registry\FlatRegistry;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class FlatContainerTest extends TestCase
{
    protected function registry(array $data = []) {
        return new FlatRegistry($data);
    }

    protected function factory($registry = null) {
        return new ContainerFactory($registry ?: $this->registry());
    }

    protected function withBasicSettings() {
        $factory = $this->factory();
        $factory->addRecord('test')->value('Hello World!');
        $factory->addRecord('lazy')->lazy(function () {
            return 'Lazy Foo';
        });

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
        $factory->addRecord('bar')->lazy(function () {
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
}
