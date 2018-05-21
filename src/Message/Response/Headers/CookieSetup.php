<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Message\Response\Headers;

use DateTime;


class CookieSetup
{
    private const MAX_TIME = 2628000;

    protected $minutes;
    protected $domain;
    protected $path     = '/';
    protected $secure   = false;
    protected $httpOnly = false;

    private $headers;
    private $name;
    private $value;

    public function __construct(string $name, ResponseHeaders $headers)
    {
        $this->name    = $name;
        $this->headers = $headers;
    }

    public function value(string $value): void
    {
        $this->value = $value;

        $this->headers->add('Set-Cookie', $this->header());
    }

    public function remove(): void
    {
        $this->value   = null;
        $this->minutes = -self::MAX_TIME;

        $this->headers->add('Set-Cookie', $this->header());
    }

    public function expires(int $minutes): CookieSetup
    {
        $this->minutes = $minutes;
        return $this;
    }

    public function permanent(): CookieSetup
    {
        $this->minutes = self::MAX_TIME;
        return $this;
    }

    public function domain(string $domain): CookieSetup
    {
        $this->domain = $domain;
        return $this;
    }

    public function path(string $path): CookieSetup
    {
        $this->path = $path;
        return $this;
    }

    public function httpOnly(bool $value = true): CookieSetup
    {
        $this->httpOnly = $value;
        return $this;
    }

    public function secure(bool $value = true): CookieSetup
    {
        $this->secure = $value;
        return $this;
    }

    private function header(): string
    {
        $header = $this->name . '=' . $this->value;

        if ($this->domain) {
            $header .= '; Domain=' . (string) $this->domain;
        }

        if ($this->path !== '/') {
            $header .= '; Path=' . $this->path;
        }

        if ($this->minutes) {
            $seconds = $this->minutes * 60;
            $expire  = (new DateTime())->setTimestamp(time() + $seconds)->format(DateTime::COOKIE);

            $header .= '; Expires=' . $expire;
            $header .= '; MaxAge=' . $seconds;
        }

        if ($this->secure) {
            $header .= '; Secure';
        }

        if ($this->httpOnly) {
            $header .= '; HttpOnly';
        }

        return $header;
    }
}
