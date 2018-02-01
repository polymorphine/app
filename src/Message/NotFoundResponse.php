<?php

namespace Shudd3r\Http\Src\Message;


use Psr\Http\Message\StreamInterface;

class NotFoundResponse extends Response
{
    public function __construct(StreamInterface $body = null) {
        parent::__construct(404, $body ?: Stream::fromResourceUri('php://temp'));
    }
}
