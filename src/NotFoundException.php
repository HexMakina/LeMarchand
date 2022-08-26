<?php

namespace HexMakina\LeMarchand;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct(string $configuration)
    {
        parent::__construct("Unkown configuration '$configuration'");
    }
}
