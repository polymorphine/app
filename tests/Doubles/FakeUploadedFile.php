<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Http\Message\UploadedFileInterface;


class FakeUploadedFile implements UploadedFileInterface
{
    public function getStream() {}
    public function moveTo($targetPath) {}
    public function getSize() {}
    public function getError() {}
    public function getClientFilename() {}
    public function getClientMediaType() {}

}
