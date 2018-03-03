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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Polymorphine\Http\Routing\Route;
use Closure;


class RequestFirewall extends Route
{
    private $condition;
    private $routes;

    public function __construct(Closure $condition, Route $routes)
    {
        $this->condition = $condition;
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        $match = $this->condition->__invoke($request);

        return ($match) ? $this->routes->forward($request) : null;
    }

    public function gateway(string $path): Route
    {
        return $this->routes->gateway($path);
    }
}
