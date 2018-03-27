<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Message\Response;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Message\Uri;


class RedirectResponseTest extends TestCase
{
    public function testInstantiation()
    {
        $responseNew = new RedirectResponse('/foo/bar/234');
        $this->assertInstanceOf(RedirectResponse::class, $responseNew);

        $responseFromUri = RedirectResponse::fromUri(Uri::fromString('/foo/bar/234'));
        $this->assertInstanceOf(RedirectResponse::class, $responseFromUri);
        $this->assertEquals($responseNew, $responseFromUri->withBody($responseNew->getBody()));
    }
}
