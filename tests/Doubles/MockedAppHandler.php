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

use Polymorphine\App\AppHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;


class MockedAppHandler extends AppHandler
{
    public bool $routeFound = true;

    public ?ResponseInterface $notFoundResponse = null;

    protected function routing(ContainerInterface $c): FakeRequestHandler
    {
        return new FakeRequestHandler(function (ServerRequestInterface $request) use ($c) {
            if (!$this->routeFound) { return $this->notFoundResponse(); }
            return new FakeResponse($request->getUri() . ': ' . $c->get('test'));
        });
    }

    private function notFoundResponse(): ResponseInterface
    {
        return $this->notFoundResponse ?: new FakeResponse();
    }
}
