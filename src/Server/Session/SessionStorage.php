<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server\Session;


class SessionStorage
{
    private $sessionData;

    public function __construct(array $data = [])
    {
        $this->sessionData = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->exists($key) ? $this->sessionData[$key] : $default;
    }

    public function set(string $key, $value = null): void
    {
        isset($value) ? $this->sessionData[$key] = $value : $this->clear($key);
    }

    public function exists(string $key): bool
    {
        return isset($this->sessionData[$key]);
    }

    public function clear(string $key): void
    {
        unset($this->sessionData[$key]);
    }

    public function getAll(): array
    {
        return $this->sessionData;
    }
}
