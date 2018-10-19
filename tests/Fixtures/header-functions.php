<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App;

use Polymorphine\App\Tests\Fixtures\HeadersState as Headers;

function header($headerLine, $remove = true)
{
    Headers::set($headerLine, $remove);
}

function headers_sent()
{
    return Headers::$outputSent;
}

function header_remove($name = null)
{
    Headers::remove($name);
}
