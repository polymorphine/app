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
use Polymorphine\Http\Routing\Exception\GatewayCallException;


trait LockedGatewayMethod
{
    public function gateway(string $path): Route
    {
        throw new GatewayCallException(sprintf('Gateway not found for path `%s`', $path));
    }
}