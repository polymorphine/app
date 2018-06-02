<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context\Session;


class SessionStorage
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->exists($key) ? $this->data[$key] : $default;
    }

    public function set(string $key, $value = null): void
    {
        isset($value) ? $this->data[$key] = $value : $this->remove($key);
    }

    public function exists(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function commit(SessionManager $sessionContext): void
    {
        $sessionContext->commit($this->data);
    }
}
