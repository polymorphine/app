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

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Route\Pattern;
use Polymorphine\Http\Routing\Exception\UnreachableEndpointException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class StaticUriMask implements Pattern
{
    private $uri;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
    }

    public static function fromUriString(string $uri)
    {
        return new self(Uri::fromString($uri));
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        return $this->compareUri($request->getUri()) ? $request : null;
    }

    public function uri(array $params, UriInterface $prototype): UriInterface
    {
        if ($scheme = $this->uri->getScheme()) {
            $this->checkConflict($scheme, $prototype->getScheme());
            $prototype = $prototype->withScheme($scheme);
        }

        if ($userInfo = $this->uri->getUserInfo()) {
            $this->checkConflict($userInfo, $prototype->getUserInfo());
            [$user, $pass] = explode(':', $this->uri->getUserInfo(), 2) + [null, null];
            $prototype = $prototype->withUserInfo($user, $pass);
        }

        if ($host = $this->uri->getHost()) {
            $this->checkConflict($host, $prototype->getHost());
            $prototype = $prototype->withHost($host);
        }

        if ($port = $this->uri->getPort()) {
            $this->checkConflict($port, $prototype->getPort() ?: '');
            $prototype = $prototype->withPort($port);
        }

        //TODO: refactoring
        if ($path = $this->uri->getPath()) {
            if ($path[0] !== '/') {
                if (!$prototypePath = $prototype->getPath()) {
                    throw new UnreachableEndpointException('Unresolved relative path');
                }
                $prototype = $prototype->withPath($prototypePath . '/' . $path);
            } else {
                $this->checkConflict($path, $prototype->getPath());
                $prototype = $prototype->withPath($path);
            }
        }

        if ($query = $this->uri->getQuery()) {
            $prototype = $prototype->withQuery($this->combinedQuery($query, $prototype->getQuery()));
        }

        return $prototype;
    }

    private function compareUri(UriInterface $uri)
    {
        $scheme = $this->uri->getScheme();
        if ($scheme && $scheme !== $uri->getScheme()) {
            return false;
        }

        $auth = $this->uri->getAuthority();
        if ($auth && $auth !== $uri->getAuthority()) {
            return false;
        }

        $path = $this->uri->getPath();
        if ($path && $uriPath = $uri->getPath()) {
            if (($path[0] === '/' && $path !== $uriPath) || ($path[0] !== '/' && !strpos($uriPath, $path))) {
                return false;
            }
        }

        $query = $this->uri->getQuery();
        if ($query && !$this->queryMatch($query, $uri->getQuery())) {
            return false;
        }

        return true;
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && $routeSegment !== $prototypeSegment) {
            $message = 'Uri conflict in `%s` prototype segment for `%s` uri';
            throw new UnreachableEndpointException(sprintf($message, $prototypeSegment, (string) $this->uri));
        }
    }

    private function combinedQuery(string $routeQuery, string $prototypeQuery)
    {
        if (empty($prototypeQuery)) {
            return $routeQuery;
        }

        $requiredSegments = $this->queryValues($routeQuery);
        $prototypeSegments = $this->queryValues($prototypeQuery);

        foreach ($requiredSegments as $name => $value) {
            if (isset($value) && isset($prototypeSegments[$name]) && $prototypeSegments[$name] !== $value) {
                $message = 'Query param conflict for `%s` segment in `%s` uri';
                throw new UnreachableEndpointException(sprintf($message, $name, (string) $this->uri));
            }

            if (!isset($value) && isset($prototypeSegments[$name])) {
                continue;
            }

            $prototypeSegments[$name] = $value;
        }

        $query = [];
        foreach ($prototypeSegments as $name => $value) {
            $query[] = isset($value) ? $name . '=' . $value : $name;
        }

        return implode('&', $query);
    }

    private function queryMatch($routeQuery, $requestQuery)
    {
        if (empty($requestQuery)) { return false; }

        $requiredSegments = $this->queryValues($routeQuery);
        $requestSegments = $this->queryValues($requestQuery);

        foreach ($requiredSegments as $key => $value) {
            if (!isset($requestSegments[$key])) {
                return false;
            }

            if (!isset($value)) {
                continue;
            }

            if ($value !== $requestSegments[$key]) {
                return false;
            }
        }

        return true;
    }

    private function queryValues(string $query): array
    {
        $segments = explode('&', $query);

        $segmentValues = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment) + [false, null];
            $segmentValues[$name] = $value;
        }

        return $segmentValues;
    }
}
