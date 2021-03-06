<?php declare(strict_types=1);

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

function header($headerLine, $remove = true): void
{
    Headers::set($headerLine, $remove);
}

function headers_sent(): bool
{
    return Headers::$outputSent;
}

function header_remove($name = null): void
{
    Headers::remove($name);
}
