<?php

namespace Shudd3r\Http\Tests;

use Psr\Container\NotFoundExceptionInterface;
use Shudd3r\Http\Src\App;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Tests\Doubles\DummyRequest;


class AppTest extends TestCase
{
    private function app(Registry $registry = null) {
        return $registry ? new App($registry) : new App();
    }

    public function testExecuteReturnsDummy() {
        $app = $this->app();
        $this->assertInstanceOf(ResponseInterface::class, $app->execute(new DummyRequest()));
    }

    public function testConfigValueIsSetInRegistry() {
        $app = $this->app();
        $app->config('test')->value('Hello World!');
        $app->config('lazy')->lazy(function () {
            return 'Lazy Foo';
        });
        $app->config('bar')->lazy(function () {
            return substr($this->get('test'), 0, 6) . $this->get('lazy') . '!';
        });

        $container = $app->execute(new DummyRequest())->container;

        $this->assertTrue($container->has('test') && $container->has('lazy') && $container->has('bar'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testInvalidContainerIdType_ThrowsException() {
        $container = $this->app()->execute(new DummyRequest())->container;
        $this->expectException(ContainerExceptionInterface::class);
        $container->has(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException() {
        $container = $this->app()->execute(new DummyRequest())->container;
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }
}
