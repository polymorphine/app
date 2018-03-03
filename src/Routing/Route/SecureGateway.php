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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class SecureGateway extends Route
{
    private $routes;

    public function __construct(Route $routes)
    {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        return ($request->getUri()->getScheme() === 'https')
            ? $this->routes->forward($request)
            : null;
    }

    public function gateway(string $path): Route
    {
        return new self($this->routes->gateway($path));
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        return $this->routes->uri($params, $prototype)->withScheme('https');
    }
}
