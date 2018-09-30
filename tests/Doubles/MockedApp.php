<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Doubles;

use Polymorphine\Http\App;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;


class MockedApp extends App
{
    public $routeFound = true;

    public $notFoundResponse;

    protected function routing(ContainerInterface $c): Router
    {
        $route = new Route\Endpoint\CallbackEndpoint(function (ServerRequestInterface $request) use ($c) {
            if (!$this->routeFound) { return $this->notFoundResponse(); }

            $body = $request->getUri() . ': ' . $c->get('test');
            return new FakeResponse($body);
        });

        return new Router($route, new FakeUri(), new FakeResponse());
    }

    private function notFoundResponse(): ResponseInterface
    {
        return $this->notFoundResponse ?: new FakeResponse();
    }
}
