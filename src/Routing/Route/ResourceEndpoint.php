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


class ResourceEndpoint extends Route
{
    const INDEX = 'INDEX';
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';

    private $path;
    private $handlers;

    public function __construct(string $path, array $handlers)
    {
        $this->path = '/' . trim($path, '/');
        $this->handlers = $handlers;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        $method = $request->getMethod();

        if ($method === self::GET) {
            return $this->forwardGetMethod($request);
        }

        if ($method === self::POST) {
            return $this->forwardPostMethod($request);
        }

        return $this->forwardWithId($method, $request);
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        $id = ($params) ? $params['id'] ?? array_shift($params) : '';
        $path = ($id) ? $this->path . '/' . $id : $this->path;

        return $prototype ? $prototype->withPath($path) : Uri::fromString($path);
    }

    protected function response($handler, $request)
    {
        return $handler($request);
    }

    protected function validId(string $id)
    {
        return is_numeric($id);
    }

    private function forwardWithId($name, ServerRequestInterface $request)
    {
        $requestPath = $request->getUri()->getPath();
        if (strpos($requestPath, $this->path) !== 0) {
            return null;
        }

        [$id, ] = explode('/', substr($requestPath, strlen($this->path) + 1), 2) + [false, false];
        if (!$this->validId($id)) {
            return null;
        }

        return $this->forwardToHandler($name, $request->withAttribute('id', $id));
    }

    private function forwardToHandler($name, ServerRequestInterface $request)
    {
        return isset($this->handlers[$name]) ? $this->response($this->handlers[$name], $request) : null;
    }

    private function forwardPostMethod(ServerRequestInterface $request)
    {
        if ($this->path !== $request->getUri()->getPath()) {
            return null;
        }

        return $this->forwardToHandler(self::POST, $request);
    }

    private function forwardGetMethod(ServerRequestInterface $request)
    {
        return ($this->path === $request->getUri()->getPath())
            ? $this->forwardToHandler(self::INDEX, $request)
            : $this->forwardWithId(self::GET, $request);
    }
}
