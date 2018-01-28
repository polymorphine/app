<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;


class DummyResponse implements ResponseInterface
{
    public $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function getProtocolVersion() {}
    public function withProtocolVersion($version) {}
    public function getHeaders() {}
    public function hasHeader($name) {}
    public function getHeader($name) {}
    public function getHeaderLine($name) {}
    public function withHeader($name, $value) {}
    public function withAddedHeader($name, $value) {}
    public function withoutHeader($name) {}
    public function getBody() {}
    public function withBody(StreamInterface $body) {}
    public function getStatusCode() {}
    public function withStatus($code, $reasonPhrase = '') {}
    public function getReasonPhrase() {}
}
