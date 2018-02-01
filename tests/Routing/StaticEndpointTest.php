<?php

namespace Shudd3r\Http\Tests\Routing;

use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Routing\StaticEndpoint;
use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyResponse;


class StaticEndpointTest extends TestCase
{
    private function route($path = '/', $callback = null) {
        if (!$callback) {
            $callback = function ($request) {
                return new DummyResponse();
            };
        }
        return new StaticEndpoint($path, $callback);
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    //TODO: add missing tests
}
