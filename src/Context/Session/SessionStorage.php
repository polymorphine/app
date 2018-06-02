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

use Psr\SimpleCache\CacheInterface;


class SessionStorage implements CacheInterface
{
    private $session;
    private $data;

    public function __construct(SessionManager $session, array $data = [])
    {
        $this->session = $session;
        $this->data    = $data;
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function set($key, $value, $ttl = null): void
    {
        isset($value) ? $this->data[$key] = $value : $this->delete($key);
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function commit(): void
    {
        $this->session->commit($this->data);
    }
}
