<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Routing\Route\Pattern;

use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class TargetPattern implements Pattern
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

    private $pattern;
    private $params;
    private $parsedPath;
    private $parsedQuery;

    public function __construct(string $pattern, array $params = [])
    {
        $this->pattern = $pattern;
        $this->params = $params;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $this->parsedPath or $this->parsedPath = $this->parsePattern();

        if (!$target = $this->normalizeTarget($request->getRequestTarget())) {
            return null;
        }

        $pattern = $this->pathPattern();
        if (!preg_match($pattern, $target, $attributes)) {
            return null;
        }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    public function uri(array $params, UriInterface $prototype): UriInterface
    {
        $this->parsedPath or $this->parsedPath = $this->parsePattern();
        $placeholders = $this->uriPlaceholders($params);
        $target = str_replace($placeholders, $params, $this->parsedPath);

        if (!$this->parsedQuery) {
            return $prototype->withPath($target);
        }

        [$path, $query] = explode('?', $target, 2);

        return $prototype->withPath($path)->withQuery($query);
    }

    private function pathPattern()
    {
        $pattern = preg_quote($this->parsedPath);
        foreach ($this->params as $name => $regexp) {
            $placeholder = '\\' . self::PARAM_DELIM_LEFT . $name . '\\' . self::PARAM_DELIM_RIGHT;
            $replace = '(?P<' . $name . '>' . $regexp . ')';
            $pattern = str_replace($placeholder, $replace, $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    private function parsePattern(): string
    {
        $types = array_keys($this->paramTypeRegexp);
        $regexp = $this->typeMarkersRegexp($types);

        $pos = strpos($this->pattern, '?');
        if ($pos !== false && $query = substr($this->pattern, $pos + 1)) {
            $this->parseQuery($query);
        }

        preg_match_all($regexp, $this->pattern, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->params[$match['id']] = $this->paramTypeRegexp[$match['type']];
        }

        $replace = array_map(function ($type) { return self::PARAM_DELIM_LEFT . $type; }, $types);

        return str_replace($replace, self::PARAM_DELIM_LEFT, $this->pattern);
    }

    private function typeMarkersRegexp(array $types): string
    {
        $regexpMarkers = array_map(function ($typeMarker) { return preg_quote($typeMarker, '/'); }, $types);
        $idPattern = '(?P<type>' . implode('|', $regexpMarkers) . ')(?P<id>[a-zA-Z]+)';

        return '/' . self::PARAM_DELIM_LEFT . $idPattern . self::PARAM_DELIM_RIGHT . '/';
    }

    private function uriPlaceholders(array $params): array
    {
        if (count($params) !== count($this->params)) {
            $message = 'Route requires %s params for `%s` path - %s provided';

            throw new UriParamsException(sprintf($message, count($this->params), $this->parsedPath, count($params)));
        }

        return array_map([$this, 'validPlaceholder'], array_keys($this->params), $this->params, $params);
    }

    private function validPlaceholder(string $name, string $type, &$value): string
    {
        $value = (string) $value;
        if (!preg_match('/^' . $type . '$/', $value)) {
            $message = 'Invalid param `%s` type for `%s` route path';

            throw new UriParamsException(sprintf($message, $name, $this->parsedPath));
        }

        return self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
    }

    private function parseQuery(string $query): void
    {
        $this->parsedQuery = $this->queryParams(explode('&', $query));
    }

    private function queryParams(array $segments): array
    {
        $params = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment, 2) + [false, null];
            $params[$name] = $value;
        }

        return $params;
    }

    private function normalizeTarget(string $target): ?string
    {
        $pos = strpos($target, '?');
        if ($pos === false) {
            return ($this->parsedQuery) ? null : $target;
        }

        [$path, $query] = explode('?', $target, 2);

        if (!$this->parsedQuery) {
            return $path;
        }

        if (!$query = $this->relevantQueryParams($query)) {
            return null;
        }

        return $path . '?' . $query;
    }

    private function relevantQueryParams(string $query): ?string
    {
        $elements = $this->queryParams(explode('&', $query));
        $segments = [];

        foreach ($this->parsedQuery as $name => $value) {
            if (!array_key_exists($name, $elements)) {
                return null;
            }

            $segments[] = ($value === null) ? $name : $name . '=' . $elements[$name];
        }

        return implode('&', $segments);
    }
}
