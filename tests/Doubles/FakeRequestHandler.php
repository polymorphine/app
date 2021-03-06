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

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class FakeRequestHandler implements RequestHandlerInterface
{
    private $handleRequest;

    public function __construct(callable $handleRequest = null)
    {
        $this->handleRequest = $handleRequest;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->handleRequest)($request);
    }
}
