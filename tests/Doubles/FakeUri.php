<?php

namespace Shudd3r\Http\Tests\Doubles;


use Psr\Http\Message\UriInterface;

class FakeUri implements UriInterface
{
    public $host;
    public $path;
    public $query;

    public function __construct($host = '', $path = '', $query = '') {
        $this->host = $host;
        $this->path = $path;
        $this->query = $query;
    }

    public function getHost() {
        return $this->host;
    }

    public function getPath() {
        return $this->path;
    }

    public function getQuery() {
        return $this->query;
    }

    public function getScheme() {}
    public function getAuthority() {}
    public function getUserInfo() {}
    public function getPort() {}
    public function getFragment() {}
    public function withScheme($scheme) {}
    public function withUserInfo($user, $password = null) {}
    public function withHost($host) {}
    public function withPort($port) {}
    public function withPath($path) {}
    public function withQuery($query) {}
    public function withFragment($fragment) {}
    public function __toString() {}
}
