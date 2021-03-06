<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests\Doubles;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class FakeMiddleware implements MiddlewareInterface
{
    public string $bodyWrap;
    public bool   $inContext = false;

    public function __construct(string $bodyWrap = 'processed')
    {
        $this->bodyWrap = $bodyWrap;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->inContext = true;

        $response = $handler->handle($request);
        $body     = $this->bodyWrap . ' ' . $response->getBody() . ' ' . $this->bodyWrap;
        if ($requestInfo = $request->getAttribute('middleware')) {
            $body = $requestInfo . ': ' . $body;
        }

        return $response->withBody(new FakeStream($body));
    }
}
