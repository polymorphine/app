<?php

namespace Shudd3r\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\App;
use Shudd3r\Http\Src\Container\Factory;
use Shudd3r\Http\Src\Message\NotFoundResponse;
use Shudd3r\Http\Tests\Doubles;


class AppTest extends TestCase
{
    private function app(Factory $factory = null) {
        return $factory
            ? new Doubles\MockedApp($factory)
            : new Doubles\MockedApp(new Doubles\MockedContainerFactory());
    }

    public function testInstantiation() {
        $this->assertInstanceOf(App::class, new Doubles\MockedApp());
        $this->assertInstanceOf(App::class, $this->app());
    }

    public function testConfig_ReturnsRegistryInput() {
        $app = $this->app();
        $this->assertInstanceOf(Factory\ContainerRecordEntry::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration() {
        $app = $this->app();
        $app->routeFound = true;
        $response = $app->execute(new Doubles\DummyRequest());
        $this->assertSame('example.com/foo/bar: Hello World!', $response->body);
    }

    public function testFallbackNotFoundRoute() {
        $app = $this->app();
        $app->routeFound = false;

        $app->overrideParent = false;
        $response = $app->execute(new Doubles\DummyRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(NotFoundResponse::class, $response);

        $app->overrideParent = true;
        $response = $app->execute(new Doubles\DummyRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Doubles\DummyResponse::class, $response);
        $this->assertSame('Not Found', $response->body);
    }

    public function testContainerSettingsArePassedToFactory() {
        $factory = new Doubles\MockedContainerFactory();
        $app = $this->app($factory);
        $app->config('testValue')->value('value');
        $app->config('testCallback')->lazy(function () { return 'ok'; });

        $this->assertSame('value', $factory->container['testValue']);
        $this->assertSame('ok', $factory->container['testCallback']->__invoke());
    }
}
