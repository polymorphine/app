<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Message\Request;

use Polymorphine\Http\Tests\Message\UploadedFileTest as TestConfig;

function move_uploaded_file($filename, $destination)
{
    if (TestConfig::$forceNativeFunctionErrors) { return false; }

    return copy($filename, $destination);
}
