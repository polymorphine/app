<?php

namespace Polymorphine\Http\Routing\Route;


use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


interface Pattern
{
    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface;

    public function uri(array $params = [], UriInterface $prototype): UriInterface;
}
