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

use Polymorphine\Http\Message;
use Psr\Http\Message\StreamInterface;


class NotFoundResponse extends Message\Response
{
    public function __construct(StreamInterface $body = null)
    {
        parent::__construct(404, $body ?: Message\Stream::fromResourceUri('php://temp'));
    }
}
