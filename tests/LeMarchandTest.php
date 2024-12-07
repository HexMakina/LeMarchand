<?php

use PHPUnit\Framework\TestCase;

use HexMakina\LeMarchand\LeMarchand;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class LeMarchandTest extends TestCase
{
   public function testEmptyConstructor()
   {
        $res = new LeMarchand([]);
        $this->assertInstanceOf(LeMarchand::class, $res);
        $this->assertInstanceOf(ContainerInterface::class, $res);

        $this->assertFalse($res->has('test'));
   }
}