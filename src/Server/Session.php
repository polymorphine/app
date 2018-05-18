<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server;

use Polymorphine\Http\Server\Session\SessionStorage;
use Polymorphine\Http\Message\Response\Headers\CookieSetup;
use RuntimeException;


class Session
{
    private $name;
    private $id;

    /** @var SessionStorage */
    private $storage;

    public function __construct(string $name = null)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name ?? session_name();
    }

    public function storage(): SessionStorage
    {
        return $this->storage ?? $this->storage = new SessionStorage();
    }

    public function start()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('Session started outside object context');
        }

        if (isset($this->name)) { session_name($this->name); }

        session_start();

        $this->id = session_id();

        isset($this->storage) or $this->storage = new SessionStorage($_SESSION);
    }

    public function stop(CookieSetup $cookie)
    {
        if (!$data = $this->storage->getAll()) { $this->destroy($cookie); }

        if (!$this->id) {
            $this->start();
            $cookie->value($this->id);
        }

        $_SESSION = $data;
        session_write_close();
    }

    private function destroy(CookieSetup $cookie)
    {
        if (!$this->id) { return; }

        $cookie->remove();
        session_destroy();
    }
}
