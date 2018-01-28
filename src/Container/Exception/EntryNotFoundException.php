<?php

namespace Shudd3r\Http\Src\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;


class EntryNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{

}
