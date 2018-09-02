<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Doubles;

use Polymorphine\Http\App;
use Polymorphine\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;


class MockedApp extends App
{
    public $routeFound = true;

    public $notFoundResponse;

    protected function routing(ContainerInterface $c): Route
    {
        $callback = function (ServerRequestInterface $request) use ($c) {
            if (!$this->routeFound) { return $this->notFoundResponse(); }

            $body = $request->getUri() . ': ' . $c->get('test');
            return new FakeResponse($body);
        };

        return new Route\Endpoint\CallbackEndpoint($callback);
    }

    protected function notFoundResponse(): ResponseInterface
    {
        return $this->notFoundResponse ?: new FakeResponse();
    }

    protected function baseUri(): UriInterface
    {
        return new FakeUri();
    }
}
