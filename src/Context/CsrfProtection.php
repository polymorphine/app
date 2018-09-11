<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context;

use Polymorphine\Http\Context\CsrfProtection\CsrfToken;


interface CsrfProtection
{
    public function appSignature(): CsrfToken;

    public function resetToken(): void;
}
