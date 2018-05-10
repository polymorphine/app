<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\App;
use Polymorphine\Container\Setup;
use Polymorphine\Http\Message\Response\NotFoundResponse;


class AppTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(App::class, $this->app());
    }

    public function testConfig_ReturnsRegistryInput()
    {
        $app = $this->app();
        $this->assertInstanceOf(Setup\RecordSetup::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration()
    {
        $app = $this->app([
            'test' => new Setup\Record\DirectRecord('Hello World!')
        ]);
        $app->routeFound = true;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertSame('//example.com/foo/bar: Hello World!', $response->body);
    }

    public function testFallbackNotFoundRoute()
    {
        $app = $this->app();

        $app->routeFound     = false;
        $app->overrideParent = false;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(NotFoundResponse::class, $response);

        $app->overrideParent = true;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Doubles\FakeResponse::class, $response);
        $this->assertSame('Not Found', $response->body);
    }

    private function app(array $records = [])
    {
        return new Doubles\MockedApp($records);
    }
}
