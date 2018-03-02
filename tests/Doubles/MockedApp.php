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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\App;


class MockedApp extends App
{
    public $routeFound = false;
    public $overrideParent = false;

    protected function routing(ContainerInterface $c): Route
    {
        return new MockedRoute(
            '',
            $this->routeFound ? function (ServerRequestInterface $request) use ($c) {
                $body = $request->getUri() . ': ' . $c->get('test');

                return new DummyResponse($body);
            } : null
        );
    }

    protected function notFoundResponse()
    {
        return $this->overrideParent ? new DummyResponse('Not Found') : parent::notFoundResponse();
    }
}
