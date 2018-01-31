<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\UriInterface;


interface Gateway
{
    public function uri(array $params): UriInterface;
}
