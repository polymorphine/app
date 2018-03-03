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


class SchemeGateway extends Route
{
    private $route;
    private $scheme;

    public function __construct(Route $route, string $scheme = 'https')
    {
        $this->route = $route;
        $this->scheme = $scheme;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        return ($request->getUri()->getScheme() === $this->scheme)
            ? $this->route->forward($request)
            : null;
    }

    public function gateway(string $path): Route
    {
        return new self($this->route->gateway($path), $this->scheme);
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        return $this->route->uri($params, $prototype)->withScheme($this->scheme);
    }
}
