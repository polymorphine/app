<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests\Fixtures;


class ShutdownState
{
    public static $override = false;
    public static $callback;
    public static $status;
    public static $outputBufferCleared = false;

    public static function reset()
    {
        self::$override            = false;
        self::$callback            = null;
        self::$status              = null;
        self::$outputBufferCleared = false;
    }
}
