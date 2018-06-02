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
    //TODO: TTL

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
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
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
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function commit(): void
    {
        $this->session->commit($this->data);
    }
}
