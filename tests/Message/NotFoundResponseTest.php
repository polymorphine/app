<?php

namespace Polymorphine\Http\Tests\Message;

use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\Message\NotFoundResponse;
use PHPUnit\Framework\TestCase;


class NotFoundResponseTest extends TestCase
{
    public function testInstantiation() {
        $response = new NotFoundResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    //TODO: implement named constructor for string body
}
