<?php

use PHPUnit\Framework\TestCase;

use HexMakina\LeMarchand\LeMarchand;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class LeMarchandKadroSettingsTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $res = new LeMarchand(require(__DIR__.'/data_settings.php'));
        $this->assertInstanceOf(LeMarchand::class, $res);

    }

}