<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Routing\Gateway;
use Shudd3r\Http\Src\Message\Uri;


class EndpointGateway implements Gateway
{
    public function uri(array $params): UriInterface {
        return new Uri();
    }
}
