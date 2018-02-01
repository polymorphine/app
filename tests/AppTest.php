<?php

namespace Shudd3r\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Tests\Doubles\FakeRegistry;
use Shudd3r\Http\Tests\Doubles\MockedApp;
use Shudd3r\Http\Tests\Doubles\DummyRequest;


class AppTest extends TestCase
{
    private function app(Registry $registry = null) {
        return $registry ? new MockedApp($registry) : new MockedApp(new FakeRegistry());
    }

    public function testExecute_ReturnsResponse() {
        $app = $this->app();
        $response = $app->execute(new DummyRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Not Found', $response->body);
    }

    public function testConfig_ReturnsRegistryInput() {
        $app = $this->app();
        $this->assertInstanceOf(RegistryInput::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration() {
        $app = $this->app();
        $app->routeFound = true;
        $response = $app->execute(new DummyRequest());
        $this->assertSame('example.com/foo/bar: Hello World!', $response->body);
    }
}
