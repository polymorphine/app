<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server\Middleware;

use Polymorphine\Http\Tests\Fixtures\SessionGlobalState;

function session_start()
{
    global $_SESSION;

    $_SESSION = SessionGlobalState::$data;

    SessionGlobalState::$status = PHP_SESSION_ACTIVE;
    SessionGlobalState::$id     = '12345657890ABCD';
}

function session_status()
{
    return SessionGlobalState::$status;
}

function session_name(string $name = null)
{
    return $name ? SessionGlobalState::$name = $name : SessionGlobalState::$name;
}

function session_id(string $id = null)
{
    return $id ? SessionGlobalState::$id = $id : SessionGlobalState::$id;
}

function session_write_close()
{
    global $_SESSION;

    SessionGlobalState::$status = PHP_SESSION_NONE;
    SessionGlobalState::$id     = '';
    SessionGlobalState::$data   = $_SESSION;
    $_SESSION                          = null;
}

function session_destroy()
{
    global $_SESSION;

    $_SESSION = null;

    SessionGlobalState::$data   = [];
    SessionGlobalState::$status = PHP_SESSION_NONE;
    SessionGlobalState::$id     = '';
}
