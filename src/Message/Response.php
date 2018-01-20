<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;


class Response implements ResponseInterface
{
    use StatusCodes;
    use MessageMethods;

    private $status;
    private $reason;

    public function __construct($statusCode = 200, StreamInterface $body, array $headers = [], array $params = []) {
        $this->status  = $statusCode;
        $this->body    = $body;
        $this->reason  = isset($params['reason']) ? $params['reason'] : $this->resolveReasonPhrase();
    }

    public function getStatusCode() {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = '') {
        $clone = clone $this;
        $clone->status = $code;
        $clone->reason = $clone->resolveReasonPhrase($reasonPhrase);
        return $clone;
    }

    public function getReasonPhrase() {
        return $this->reason;
    }

    private function resolveReasonPhrase($reason = '') {
        if (empty($reason) && isset($this->statusCodes[$this->status])) {
            $reason = $this->statusCodes[$this->status];
        }

        return $reason;
    }
}
