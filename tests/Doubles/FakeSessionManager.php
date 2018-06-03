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

use Polymorphine\Http\Context\Session;
use Polymorphine\Http\Context\SessionManager;


class FakeSessionManager implements SessionManager
{
    public $data;

    public function startSession(): void
    {
    }

    public function session(): Session
    {
    }

    public function commitSession(array $data): void
    {
        $this->data = $data;
    }
}
