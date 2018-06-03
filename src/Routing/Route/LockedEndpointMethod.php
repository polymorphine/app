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

use Psr\Http\Message\UriInterface;
use Polymorphine\Http\Routing\Exception\EndpointCallException;


trait LockedEndpointMethod
{
    public function uri(UriInterface $prototype, array $params = []): UriInterface
    {
        throw new EndpointCallException('Uri not defined in gateway route');
    }
}
