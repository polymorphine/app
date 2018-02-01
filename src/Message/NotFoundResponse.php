<?php

namespace Shudd3r\Http\Src\Message;


class NotFoundResponse extends Response
{
    public function __construct() {
        parent::__construct(404, Stream::fromResourceUri('php://temp'));
    }
}
