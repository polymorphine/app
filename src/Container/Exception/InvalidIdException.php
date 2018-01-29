<?php

namespace Shudd3r\Http\Src\Container\Exception;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

class InvalidIdException extends InvalidArgumentException implements ContainerExceptionInterface
{

}
