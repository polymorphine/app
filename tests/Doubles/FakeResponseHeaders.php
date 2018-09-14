<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Doubles;

use Polymorphine\Http\Context\ResponseHeaders;
use Polymorphine\Http\Context\ResponseHeaders\CookieSetup;


class FakeResponseHeaders implements ResponseHeaders
{
    public $data = [];

    public function cookie(string $name, array $attributes = []): CookieSetup
    {
        return new CookieSetup($name, $this, $attributes);
    }

    public function add(string $name, string $header): void
    {
        $this->data[$name][] = $header;
    }
}
