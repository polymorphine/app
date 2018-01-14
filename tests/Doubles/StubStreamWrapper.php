<?php

namespace Shudd3r\Http\Tests\Doubles;


class StubStreamWrapper
{
    public static $openReturns;
    public static $tellReturns;
    public static $eofReturns;
    public static $seekReturns;
    public static $metaReturns;
    public static $writtenData;

    public static function init() {
        self::$openReturns = true;
        self::$tellReturns = 0;
        self::$eofReturns  = false;
        self::$seekReturns = true;
        self::$metaReturns = true;
        self::$writtenData = '';
    }

    public function stream_open($path, $mode, $options, &$opened_path) {
        self::init();
        return true;
    }

    public function stream_read($count) {
        return substr(self::$writtenData, 0, $count);
    }

    public function stream_write($data) {
        self::$writtenData = $data;
        return strlen($data);
    }

    public function stream_tell() {
        return self::$tellReturns;
    }

    public function stream_eof() {
        return self::$eofReturns;
    }

    public function stream_seek($offset, $whence) {
        return self::$seekReturns;
    }

    public function stream_metadata($path, $option, $var) {
        return self::$metaReturns;
    }
}
