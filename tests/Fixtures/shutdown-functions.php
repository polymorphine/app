<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App;

use Polymorphine\App\Tests\Fixtures\ShutdownState;

function register_shutdown_function(callable $callback): void
{
    ShutdownState::$override ? ShutdownState::$callback = $callback : \register_shutdown_function($callback);
}

function http_response_code(int $code = null): void
{
    ShutdownState::$override ? ShutdownState::$status = $code : \http_response_code($code);
}

function error_get_last(): ?array
{
    return ShutdownState::$override ? [] : \error_get_last();
}

function ob_end_clean(): bool
{
    return ShutdownState::$override ? ShutdownState::$outputBufferCleared = true : \ob_end_clean();
}
