<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context;


interface Session
{
    public function get(string $key, $default = null);

    public function set(string $key, $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function clear();

    public function commit();
}
