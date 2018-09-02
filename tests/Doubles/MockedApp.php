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
    public $routeFound = false;

    /** @var ResponseInterface */
    public $notFound;

    protected function routing(ContainerInterface $c): Route
    {
        return new MockedRoute(
            '',
            $this->routeFound ? function (ServerRequestInterface $request) use ($c) {
                $body = $request->getUri() . ': ' . $c->get('test');

                return new FakeResponse($body);
            } : null
        );
    }

    protected function notFoundResponse(): ResponseInterface
    {
        return $this->notFound ?: new FakeResponse();
    }

    protected function baseUri(): UriInterface
    {
        return new FakeUri();
    }
}
