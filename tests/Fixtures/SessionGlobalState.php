<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Fixtures;


class SessionGlobalState
{
    public static $sessionName   = 'PHPSESS';
    public static $sessionId     = '';
    public static $sessionStatus = PHP_SESSION_NONE;
    public static $sessionData   = [];

    public static function reset()
    {
        self::$sessionName   = 'PHPSESS';
        self::$sessionId     = '';
        self::$sessionStatus = PHP_SESSION_NONE;
        self::$sessionData   = [];
    }
}
