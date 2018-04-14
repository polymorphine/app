<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Routing;

use Polymorphine\Http\Message\Response\NotFoundResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RouteHandler implements RequestHandlerInterface
{
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->route->forward($request);
        return $response ?: new NotFoundResponse();
    }
}
