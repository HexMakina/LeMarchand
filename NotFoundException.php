<?php

namespace HexMakina\LeMarchand;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct($configuration)
    {
        return parent::__construct("Unkown configuration '$configuration'");
    }
}
