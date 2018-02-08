<?php

namespace Shudd3r\Http\Tests\Routing\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Routing\Route\PathGuard;
use Shudd3r\Http\Tests\Doubles;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class PathGuardTest extends TestCase
{
    private function route($path = '/', $route = null) {
        return new PathGuard($path, $route ?: new Doubles\MockedRoute('default'));
    }

    private function request($path = '/') {
        $request = new Doubles\DummyRequest();
        $request->uri = new FakeUri('example.com', $path);
        return $request;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testNotMatchingPath_ReturnsNull() {
        $route = $this->route('/foo/bar');
        $this->assertNull($route->forward($this->request()));
        $this->assertNull($route->forward($this->request('/bar/foo')));
    }

    public function testMatchingPathForwardsRequest() {
        $route = $this->route('/foo/bar');
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/foo/bar')));
        $this->assertSame('default', $route->forward($this->request('/foo/bar'))->body);
    }

    public function testGatewayCallIsPassedToWrappedRoute() {
        $route = $this->route('/foo/bar');
        $this->assertSame('path.forwarded', $route->gateway('path.forwarded')->path);
    }
}
