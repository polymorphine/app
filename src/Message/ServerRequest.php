<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ServerRequestInterface;


class ServerRequest implements ServerRequestInterface
{
    use RequestMethods;

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
