<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ResponseInterface;


class Response implements ResponseInterface
{
    use MessageMethods;

    public function getStatusCode() {}
    public function withStatus($code, $reasonPhrase = '') {}
    public function getReasonPhrase() {}
}
