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

use Polymorphine\Http\Context\SessionManager;
use Polymorphine\Http\Context\Session;


class SessionStorage implements Session
{
    private $manager;
    private $data;

    public function __construct(SessionManager $manager, array $data = [])
    {
        $this->manager = $manager;
        $this->data    = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function commit(): void
    {
        $this->manager->commitSession($this->data);
    }
}
