<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    public function __construct(string $configuration)
    {
        $configuration = json_encode(var_export($configuration, true));
        parent::__construct("HellBound Error using '$configuration'");
    }
}
