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
use Polymorphine\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Closure;


class PatternEndpoint extends Route
{
    private $method;
    private $callback;
    private $pattern;

    public function __construct(string $method, Pattern $pattern, Closure $callback)
    {
        $this->method = $method;
        $this->callback = $callback;
        $this->pattern = $pattern;
    }

    public static function post(string $path, Closure $callback, array $params = [])
    {
        return new self('POST', new Pattern\DynamicTargetMask($path, $params), $callback);
    }

    public static function get(string $path, Closure $callback, array $params = [])
    {
        return new self('GET', new Pattern\DynamicTargetMask($path, $params), $callback);
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        return ($this->methodMatch($request) && $request = $this->pattern->matchedRequest($request))
            ? $this->callback->__invoke($request)
            : null;
    }

    public function uri(array $params = [], UriInterface $responseUri = null): UriInterface
    {
        return $this->pattern->uri($params, $responseUri ?: new Uri());
    }

    private function methodMatch(ServerRequestInterface $request): bool
    {
        return $this->method === $request->getMethod();
    }
}
