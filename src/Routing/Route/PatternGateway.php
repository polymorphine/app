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

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class PatternGateway extends Route
{
    private $pattern;
    private $route;

    public function __construct(Pattern $pattern, Route $route)
    {
        $this->pattern = $pattern;
        $this->route = $route;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        $request = $this->pattern->matchedRequest($request);

        return $request ? $this->route->forward($request) : null;
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        return $this->pattern->uri($params, $prototype ?: new Uri());
    }

    public function gateway(string $path): Route
    {
        return new self($this->pattern, $this->route->gateway($path));
    }
}
