<?php

namespace Shudd3r\Http\Tests\Message\Doubles;


use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Shudd3r\Http\Src\Message\MessageMethods;

class MessageMethodsShell implements MessageInterface
{
    use MessageMethods;

    public function __construct(StreamInterface $body, array $headers, $version = '1.1') {
        $this->body    = $body;
        $this->version = $this->validProtocolVersion($version);
        $this->loadHeaders($headers);
    }
}
