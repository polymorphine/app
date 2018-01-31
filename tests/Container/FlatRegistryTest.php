<?php

namespace Shudd3r\Http\Tests\Container;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Container\FlatRegistry;
use Shudd3r\Http\Src\Container\Registry;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class FlatRegistryTest extends TestCase
{
    protected function registry(array $data = []) {
        return new FlatRegistry($data);
    }

    private function withBasicSettings() {
        $registry = $this->registry();
        $registry->entry('test')->value('Hello World!');
        $registry->entry('lazy')->lazy(function () {
            return 'Lazy Foo';
        });

        return $registry;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Registry::class, $this->registry());
    }

    public function testConfiguredRecordsAreAvailableFromContainer() {
        $container = $this->withBasicSettings()->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContaine() {
        $registry = $this->withBasicSettings();
        $registry->entry('bar')->lazy(function () {
            return substr($this->get('test'), 0, 6) . $this->get('lazy') . '!';
        });
        $container = $registry->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testInvalidContainerIdType_ThrowsException() {
        $container = $this->registry()->container();
        $this->expectException(ContainerExceptionInterface::class);
        $container->has(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException() {
        $container = $this->registry()->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }
}