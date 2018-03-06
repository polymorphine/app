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
use Polymorphine\Http\Routing\Exception\UriParamsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Closure;


class DynamicEndpoint extends Route
{
    const PARAM_DELIM_LEFT = '{';
    const PARAM_DELIM_RIGHT = '}';

    const PARAM_TYPE_NUM = '#';
    const PARAM_TYPE_NAME = '%';
    const PARAM_TYPE_SLUG = '$';

    protected $paramTypeRegexp = [
        self::PARAM_TYPE_NUM => '[1-9][0-9]*',
        self::PARAM_TYPE_NAME => '[a-zA-Z0-9]+',
        self::PARAM_TYPE_SLUG => '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'
    ];

    private $method;
    private $path;
    private $callback;
    private $params = [];
    private $parsedPath;

    public function __construct(string $method, string $path, Closure $callback, $params = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->callback = $callback;
        $this->params = $params;
    }

    public static function post(string $path, Closure $callback)
    {
        return new self('POST', $path, $callback);
    }

    public static function get(string $path, Closure $callback)
    {
        return new self('GET', $path, $callback);
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        return ($this->methodMatch($request) && $this->targetMatch($request))
            ? $this->callback->__invoke($request)
            : null;
    }

    public function uri(array $params = [], UriInterface $responseUri = null): UriInterface
    {
        $this->extractParams();
        if (count($params) !== count($this->params)) {
            $message = 'Route requires %s params for `%s` path - %s provided';

            throw new UriParamsException(sprintf($message, count($this->params), $this->parsedPath, count($params)));
        }

        $validParam = function ($name, $type, $value) {
            $value = (string) $value;
            $regexp = '/^' . $this->paramTypeRegexp[$type] . '$/';
            if (!preg_match($regexp, $value)) {
                $message = 'Invalid param `%s` type for `%s` route path';

                throw new UriParamsException(sprintf($message, $name, $this->parsedPath));
            }

            return self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
        };

        $placeholders = array_map($validParam, array_keys($this->params), $this->params, $params);

        $path = str_replace($placeholders, $params, $this->parsedPath);
        $uri = $responseUri ?: new Uri();

        return $uri->withPath($path);
    }

    private function methodMatch(ServerRequestInterface $request): bool
    {
        return $this->method === $request->getMethod();
    }

    private function targetMatch(ServerRequestInterface &$request): bool
    {
        $target = $request->getRequestTarget();
//TODO: query handling
//        if ($pos = strpos($target, '?')) {
//            $target = substr($target, 0, $pos);
//        }

        if (!preg_match($this->pathPattern(), $target, $attributes)) {
            return false;
        }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return true;
    }

    private function pathPattern()
    {
        $this->extractParams();
        $pattern = $this->parsedPath;
        foreach ($this->params as $name => $regexp) {
            $placeholder = self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
            $replace = '(?P<' . $name . '>' . $this->paramTypeRegexp[$regexp] . ')';
            $pattern = str_replace($placeholder, $replace, $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    private function extractParams(): void
    {
        if ($this->parsedPath) {
            return;
        }
        $types = array_keys($this->paramTypeRegexp);

        $typesPattern = implode('|', array_map('preg_quote', $types));
        $idPattern = '(?P<type>' . $typesPattern . ')(?P<id>[a-zA-Z]+)';
        $regexp = '/' . self::PARAM_DELIM_LEFT . $idPattern . self::PARAM_DELIM_RIGHT . '/';

        preg_match_all($regexp, $this->path, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->params[$match['id']] = $match['type'];
        }

        $replace = array_map(function ($type) { return self::PARAM_DELIM_LEFT . $type; }, $types);
        $this->parsedPath = str_replace($replace, self::PARAM_DELIM_LEFT, $this->path);
    }
}
