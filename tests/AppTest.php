<?php

namespace Shudd3r\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\App;
use Shudd3r\Http\Src\Container\Factory;
use Shudd3r\Http\Src\InputProxy;
use Shudd3r\Http\Src\Message\NotFoundResponse;
use Shudd3r\Http\Tests\Doubles\DummyResponse;
use Shudd3r\Http\Tests\Doubles\MockedContainerFactory;
use Shudd3r\Http\Tests\Doubles\MockedApp;
use Shudd3r\Http\Tests\Doubles\DummyRequest;


class AppTest extends TestCase
{
    private function app(Factory $factory = null) {
        return $factory ? new MockedApp($factory) : new MockedApp(new MockedContainerFactory());
    }

    public function testInstantiation() {
        $this->assertInstanceOf(App::class, new MockedApp());
        $this->assertInstanceOf(App::class, $this->app());
    }

    public function testConfig_ReturnsRegistryInput() {
        $app = $this->app();
        $this->assertInstanceOf(InputProxy::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration() {
        $app = $this->app();
        $app->routeFound = true;
        $response = $app->execute(new DummyRequest());
        $this->assertSame('example.com/foo/bar: Hello World!', $response->body);
    }

    public function testFallbackNotFoundRoute() {
        $app = $this->app();
        $app->routeFound = false;

        $app->overrideParent = false;
        $response = $app->execute(new DummyRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(NotFoundResponse::class, $response);

        $app->overrideParent = true;
        $response = $app->execute(new DummyRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(DummyResponse::class, $response);
        $this->assertSame('Not Found', $response->body);
    }

    public function testContainerSettingsArePassedToFactory() {
        $factory = new MockedContainerFactory();
        $app = $this->app($factory);
        $app->config('testValue')->value('value');
        $app->config('testCallback')->lazy(function () { return 'ok'; });

        $this->assertSame('value', $factory->values['testValue']);
        $this->assertSame('ok', $factory->closures['testCallback']->__invoke());
    }
}
