<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Message\Doubles;

use Psr\Http\Message\UriInterface;


class FakeUri implements UriInterface
{
    public $host;
    public $path;
    public $query;
    public $scheme = 'http';

    public function __construct($host = '', $path = '', $query = '')
    {
        $this->host = $host;
        $this->path = $path;
        $this->query = $query;
    }

    public function __toString()
    {
        return $this->host . $this->path . $this->query;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPath()
    {
        return $this->path ?: '/';
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function withPath($path)
    {
        return new self($this->host, $path, $this->query);
    }

    public function withQuery($query)
    {
        return new self($this->host, $this->path, $query);
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getAuthority()
    {
    }

    public function getUserInfo()
    {
    }

    public function getPort()
    {
    }

    public function getFragment()
    {
    }

    public function withScheme($scheme)
    {
        $uri = new self($this->host, $this->path, $this->query);
        $uri->scheme = $scheme;

        return $uri;
    }

    public function withUserInfo($user, $password = null)
    {
    }

    public function withHost($host)
    {
    }

    public function withPort($port)
    {
    }

    public function withFragment($fragment)
    {
    }
}
