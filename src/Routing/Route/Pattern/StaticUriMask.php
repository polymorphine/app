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
        $uri = $request->getUri();

        $match = $this->match($this->uri->getScheme(), $uri->getScheme()) &&
            $this->match($this->uri->getAuthority(), $uri->getAuthority()) &&
            $this->matchPath($this->uri->getPath(), $uri->getPath());

        return ($match) ? $this->matchQuery($this->uri->getQuery(), $request) : null;
    }

    public function uri(array $params, UriInterface $prototype): UriInterface
    {
        $prototype = $this->setScheme($prototype);
        $prototype = $this->setUserInfo($prototype);
        $prototype = $this->setHost($prototype);
        $prototype = $this->setPort($prototype);
        $prototype = $this->setPath($prototype);

        return $this->setQuery($params, $prototype);
    }

    protected function queryPattern(string $queryString): Pattern
    {
        return new StaticQueryPattern($queryString);
    }

    private function match($routeSegment, $requestSegment)
    {
        return !$routeSegment || $routeSegment === $requestSegment;
    }

    private function matchPath($routePath, $requestPath)
    {
        if (!$routePath || !$requestPath) { return true; }
        if ($routePath[0] === '/') {
            return $routePath === $requestPath;
        }

        return strpos($requestPath, $routePath) > 0;
    }

    private function matchQuery($query, ServerRequestInterface $request)
    {
        return ($query) ? $this->queryPattern($query)->matchedRequest($request) : $request;
    }

    private function setScheme(UriInterface $prototype)
    {
        if (!$scheme = $this->uri->getScheme()) { return $prototype; }
        $this->checkConflict($scheme, $prototype->getScheme());

        return $prototype->withScheme($scheme);
    }

    private function setUserInfo(UriInterface $prototype)
    {
        if (!$userInfo = $this->uri->getUserInfo()) { return $prototype; }
        $this->checkConflict($userInfo, $prototype->getUserInfo());

        [$user, $pass] = explode(':', $this->uri->getUserInfo(), 2) + [null, null];

        return $prototype->withUserInfo($user, $pass);
    }

    private function setHost(UriInterface $prototype)
    {
        if (!$host = $this->uri->getHost()) { return $prototype; }
        $this->checkConflict($host, $prototype->getHost());

        return $prototype->withHost($host);
    }

    private function setPort(UriInterface $prototype)
    {
        if (!$port = $this->uri->getPort()) { return $prototype; }
        $this->checkConflict($port, $prototype->getPort() ?: '');

        return $prototype->withPort($port);
    }

    private function setPath(UriInterface $prototype)
    {
        if (!$path = $this->uri->getPath()) { return $prototype; }

        $prototypePath = $prototype->getPath();
        if ($path[0] === '/') {
            $this->checkConflict($path, $prototypePath);

            return $prototype->withPath($path);
        }

        if (!$prototypePath) {
            throw new UnreachableEndpointException('Unresolved relative path');
        }

        return $prototype->withPath($prototypePath . '/' . $path);
    }

    private function setQuery(array $params, UriInterface $prototype)
    {
        if (!$query = $this->uri->getQuery()) { return $prototype; }

        return $this->queryPattern($query)->uri($params, $prototype);
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && $routeSegment !== $prototypeSegment) {
            $message = 'Uri conflict in `%s` prototype segment for `%s` uri';
            throw new UnreachableEndpointException(sprintf($message, $prototypeSegment, (string) $this->uri));
        }
    }
}
