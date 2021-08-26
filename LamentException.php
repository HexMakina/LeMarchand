<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerExceptionInterface;

class LamentException extends \Exception implements ContainerExceptionInterface
{
    public function __construct($configuration)
    {
        $configuration = json_encode(var_export($configuration, true));
        return parent::__construct("HellBound Error using '$configuration'");
    }
}
