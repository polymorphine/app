<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context\Security;


class CsrfToken
{
    private $name;
    private $signature;

    public function __construct($name, $signature)
    {
        $this->name      = $name;
        $this->signature = $signature;
    }

    public function name()
    {
        return $this->name;
    }

    public function signature()
    {
        return $this->signature;
    }
}
