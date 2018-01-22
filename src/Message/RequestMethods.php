<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


trait RequestMethods
{
    use MessageMethods;

    private $method;
    private $uri;
    private $target;

    public function getRequestTarget() {
        return $this->target ?? '/';
    }

    public function withRequestTarget($requestTarget) {
        $clone = clone $this;
        $clone->target = $this->validRequestTarget($requestTarget);
        return $clone;
    }

    public function getMethod() {
        return $this->method;
    }

    public function withMethod($method) {
        $clone = clone $this;
        $clone->method = $this->validMethod($method);
        return $clone;
    }

    public function getUri(): UriInterface {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false) {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }

    private function validRequestTarget($target) {
        $invalidTarget = (!$target || !is_string($target) || $target !== '*' && !parse_url($target));
        return $invalidTarget ? null : $target;
    }

    private function validMethod($method) {
        if (!is_string($method) || $this->invalidTokenChars($method)) {
            throw new InvalidArgumentException('Invalid HTTP method name argument. Expected valid string token');
        }

        return $method;
    }
}
