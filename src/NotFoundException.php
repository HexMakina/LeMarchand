<?php

namespace HexMakina\LeMarchand;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct(String $configuration)
    {
        parent::__construct("Unkown configuration '$configuration'");
    }
}
