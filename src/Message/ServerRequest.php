<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;


class ServerRequest implements ServerRequestInterface
{
    use RequestMethods;

    public function __construct(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        array $params = []
    ) {
        $this->method = $this->validMethod($method);
        $this->uri = $uri;
        $this->body = $body;
        $this->version = isset($params['version']) ? $this->validProtocolVersion($params['version']) : '1.1';
        $this->target = isset($params['target']) ? $this->validRequestTarget($params['target']) : null;
        $this->loadHeaders($headers);
        $this->resolveHostHeader();
    }

    public function getServerParams() {}
    public function getCookieParams() {}
    public function withCookieParams(array $cookies) {}
    public function getQueryParams() {}
    public function withQueryParams(array $query) {}
    public function getUploadedFiles() {}
    public function withUploadedFiles(array $uploadedFiles) {}
    public function getParsedBody() {}
    public function withParsedBody($data) {}
    public function getAttributes() {}
    public function getAttribute($name, $default = null) {}
    public function withAttribute($name, $value) {}
    public function withoutAttribute($name) {}
}
