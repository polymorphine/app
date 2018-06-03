<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Routing\Route;

use Polymorphine\Http\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Closure;


/**
 * Route that forwards passed request in context of given Closure.
 */
class CallbackGateway implements Route
{
    private $callback;
    private $route;

    /**
     * CallbackGateway constructor.
     *
     * $callback Closure takes two parameters: ServerRequestInterface
     * and forwarding Closure - function that will pass request from
     * $callback context to given routes.
     *
     * Warning: Forward function does not guarantee getting response
     * back, because Route::forward($request) might return null.
     *
     * @param Closure $callback
     * @param Route   $route
     */
    public function __construct(Closure $callback, Route $route)
    {
        $this->callback = $callback;
        $this->route    = $route;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        $forward = function (ServerRequestInterface $request) {
            return $this->route->forward($request);
        };
        return $this->callback->__invoke($request, $forward);
    }

    public function gateway(string $path): Route
    {
        return $this->route->gateway($path);
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        return $this->route->uri($params, $prototype);
    }
}
