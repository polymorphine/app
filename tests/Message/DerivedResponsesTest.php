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
use Psr\Http\Message\ResponseInterface;


class DerivedResponsesTest extends TestCase
{
    public function testInstantiation()
    {
        $this->equivalentConstructs(
            new RedirectResponse('/foo/bar/234'),
            RedirectResponse::fromUri(Uri::fromString('/foo/bar/234'))
        );

        $this->equivalentConstructs(
            new StringResponse('<span>html</span>', 200, ['Content-Type' => 'text/html']),
            StringResponse::html('<span>html</span>')
        );

        $this->equivalentConstructs(
            new StringResponse('Hello World!', 200, ['Content-Type' => 'text/plain']),
            StringResponse::text('Hello World!')
        );

        $this->equivalentConstructs(
            new StringResponse('{"path":"some/path"}', 200, ['Content-Type' => 'application/json']),
            StringResponse::json(['path' => 'some/path'])
        );

        $this->equivalentConstructs(
            new StringResponse('{"path":"some\/path"}', 200, ['Content-Type' => 'application/json']),
            StringResponse::json(['path' => 'some/path'], 200, JSON_UNESCAPED_SLASHES)
        );
    }

    private function equivalentConstructs(ResponseInterface $responseA, ResponseInterface $responseB)
    {
        $bodyA = $responseA->getBody();
        $this->assertSame($bodyA->getContents(), $responseB->getBody()->getContents());
        $this->assertEquals($responseA, $responseB->withBody($bodyA));
    }
}
