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

        if ($path = $this->uri->getPath()) {
            $this->checkConflict($path, $prototype->getPath());
            $prototype = $prototype->withPath($path);
        }

        if ($query = $this->uri->getQuery()) {
            $this->checkConflict($query, $prototype->getQuery());
            $prototype = $prototype->withQuery($query);
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
        if ($path && $path !== $uri->getPath()) {
            return false;
        }

        $query = $this->uri->getQuery();
        if ($query && $query !== $uri->getQuery()) {
            return false;
        }

        return true;
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && $routeSegment !== $prototypeSegment) {
            $message = 'Uri conflict detected prototype `%s` does not match route `%s`';
            throw new UnreachableEndpointException(sprintf($message, $prototypeSegment, $routeSegment));
        }
    }
}
