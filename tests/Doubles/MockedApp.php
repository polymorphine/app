<?php

namespace Polymorphine\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\App;


class MockedApp extends App
{
    public $routeFound = false;
    public $overrideParent = false;

    protected function routing(ContainerInterface $c): Route {
        return new MockedRoute('', $this->routeFound
            ? function (ServerRequestInterface $request) use ($c) {
                $body = $request->getUri() . ': ' . $c->get('test');
                return new DummyResponse($body);
            } : null
        );
    }

    protected function notFoundResponse() {
        return $this->overrideParent ? new DummyResponse('Not Found') : parent::notFoundResponse();
    }
}
