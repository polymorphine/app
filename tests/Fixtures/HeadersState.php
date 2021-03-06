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


class HeadersState
{
    public static array $headers = [];
    public static bool  $outputSent = false;

    public static function reset(): void
    {
        self::$headers    = [];
        self::$outputSent = false;
    }

    public static function set(string $headerLine, $remove = true): void
    {
        [$name, $type] = explode(':', $headerLine, 2) + [false, 'STATUS'];
        if ($type === 'STATUS') {
            $name = $type;
        }

        if ($remove) { self::remove($name); }
        self::$headers[strtolower($name)][] = $headerLine;
    }

    public static function remove(string $name = null): void
    {
        if ($name === null) {
            self::$headers = [];
        }
        unset(self::$headers[strtolower($name ?? '')]);
    }
}
