<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class Uri implements UriInterface
{
    private $uri;

    private $scheme    = '';
    private $userInfo  = '';
    private $host      = '';
    private $port      = null;
    private $path      = '';
    private $query     = '';
    private $fragment  = '';

    protected $supportedSchemes = [
        'http'   => ['port' => 80],
        'https'  => ['port' => 443]
    ];

    public function __construct(string $uri = '') {
        $segments = parse_url($uri);
        if ($segments === false) {
            throw new InvalidArgumentException("Malformed URI string: '$uri'");
        }

        isset($segments['scheme']) and $this->scheme = $this->validScheme($segments['scheme']);
        isset($segments['user']) and $this->userInfo = $segments['user'];
        isset($segments['pass']) and $this->userInfo and $this->userInfo .= ':' . $segments['pass'];
        isset($segments['host']) and $this->host = $segments['host'];
        isset($segments['port']) and $this->port = $this->validPortRange((int) $segments['port']);
        isset($segments['path']) and $this->path = $segments['path'];
        isset($segments['query']) and $this->query = $segments['query'];
        isset($segments['fragment']) and $this->fragment = $segments['fragment'];
    }

    public function __toString(): string {
        isset($this->uri) or $this->uri = $this->buildUriString();
        return $this->uri;
    }

    public function getScheme(): string {
        return $this->scheme;
    }

    public function getUserInfo(): string {
        return $this->userInfo;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort() {
        $default = $this->port && $this->scheme && $this->supportedSchemes[$this->scheme]['port'] === $this->port;
        return ($default) ? null : $this->port;
    }

    public function getAuthority(): string {
        if (!$this->host) { return ''; }
        $user = $this->userInfo ? $this->userInfo . '@' : '';
        $port = $this->getPort();
        return $user . $this->host . ($port ? ':' . $port : '');
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getQuery(): string {
        return $this->query;
    }

    public function getFragment(): string {
        return $this->fragment;
    }

    public function withScheme($scheme): UriInterface {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('URI scheme must be a string');
        }

        $clone = clone $this;
        $clone->scheme = $clone->validScheme($scheme);
        return $clone;
    }

    public function withUserInfo($user, $password = null): UriInterface {
        if (!is_string($user)) {
            throw new InvalidArgumentException('URI user must be a string');
        }

        if (!is_null($password) && !is_string($password)) {
            throw new InvalidArgumentException('URI password must be a string or null');
        }

        empty($password) or $password = ':' . $password;

        $clone = clone $this;
        $clone->userInfo = $user . $password;
        return $clone;
    }

    public function withHost($host): UriInterface {
        if (!is_string($host)) {
            throw new InvalidArgumentException('URI host must be a string');
        }

        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    public function withPort($port): UriInterface {
        if (!is_int($port) && !is_null($port)) {
            throw new InvalidArgumentException('Invalid port parameter - expected int<1-65535> or null');
        }

        $clone = clone $this;
        $clone->port = is_null($port) ? null : $clone->validPortRange($port);
        return $clone;
    }

    public function withPath($path): UriInterface {
        if (!is_string($path)) {
            throw new InvalidArgumentException('URI path must be a string');
        }

        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    public function withQuery($query): UriInterface {
        if (!is_string($query)) {
            throw new InvalidArgumentException('URI query must be a string');
        }

        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function withFragment($fragment): UriInterface {
        if (!is_string($fragment)) {
            throw new InvalidArgumentException('URI fragment must be a string.');
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    protected function buildUriString(): string {
        $uri       = '';
        $authority = $this->getAuthority();

        if ($this->scheme) { $uri .= $this->scheme . ':'; }
        if ($authority) { $uri .= '//' . $authority; }
        if ($this->path) { $uri .= ($authority) ? $this->pathForAuthority() : $this->pathWithoutAuthority(); }
        if ($this->query) { $uri .= '?' . $this->query; }
        if ($this->fragment) { $uri .= '#' . $this->fragment; }

        return $uri ?: '/';
    }

    private function pathForAuthority() {
        return ($this->path[0] !== '/') ? '/' . $this->path : $this->path;
    }

    private function pathWithoutAuthority() {
        return (substr($this->path, 0, 2) === '//') ? preg_replace('#^\/\/+#', '/', $this->path) : $this->path;
    }

    private function validScheme(string $scheme) {
        if (empty($scheme)) { return $scheme; }

        $scheme = strtolower($scheme);
        if (!isset($this->supportedSchemes[$scheme])) {
            throw new InvalidArgumentException('Unsupported scheme');
        }

        return $scheme;
    }

    private function validPortRange(int $port) {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid port range. Expected <1-65535> value');
        }

        return $port;
    }

    public function __clone() {
        unset($this->uri);
    }
}
