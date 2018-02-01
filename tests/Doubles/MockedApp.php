<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\App;


class MockedApp extends App
{
    public $routeFound = false;
    public $overrideParent = false;

    protected function routing(ContainerInterface $c): Route {
        $route = new MockedRoute();
        $route->callback = $this->routeFound ? function (ServerRequestInterface $request) use ($c) {
            $body = $request->getUri() . ': ' . $c->get('test');
            return new DummyResponse($body);
        } : null;

        return $route;
    }

    protected function notFoundResponse() {
        return $this->overrideParent ? new DummyResponse('Not Found') : parent::notFoundResponse();
    }
}
