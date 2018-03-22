<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Message;

use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\Message\Response\NotFoundResponse;
use PHPUnit\Framework\TestCase;


class NotFoundResponseTest extends TestCase
{
    public function testInstantiation()
    {
        $response = new NotFoundResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    //TODO: implement named constructor for string body
}
