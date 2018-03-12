<?php

namespace Polymorphine\Http\Routing\Route\Pattern;


use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Route\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class UriMask implements Pattern
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

    public function uri(array $params, UriInterface $u): UriInterface
    {
        if ($scheme = $this->uri->getScheme()) {
            $u = $u->withScheme($scheme);
        }

        if ($host = $this->uri->getHost()) {
            $u = $u->withHost($host);
        }

        if ($path = $this->uri->getPath()) {
            $u = $u->withPath($path);
        }

        if ($query = $this->uri->getQuery()) {
            $u = $u->withQuery($query);
        }

        return $u;
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
}
