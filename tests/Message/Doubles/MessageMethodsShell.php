<?php

namespace Polymorphine\Http\Tests\Message\Doubles;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Polymorphine\Http\Message\MessageMethods;


class MessageMethodsShell implements MessageInterface
{
    use MessageMethods;

    public function __construct(StreamInterface $body, array $headers, $version = '1.1') {
        $this->body    = $body;
        $this->version = $this->validProtocolVersion($version);
        $this->loadHeaders($headers);
    }
}
