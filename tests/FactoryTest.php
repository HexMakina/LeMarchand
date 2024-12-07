<?php

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use HexMakina\LeMarchand\Factory;
use HexMakina\LeMarchand\LeMarchand;

class FactoryTest extends TestCase
{
    private $container;
    private $factory;

    protected function setUp(): void
    {
        $this->container = new LeMarchand([]);
        $this->factory = new Factory($this->container);
    }

    public function testServe()
    {
        $this->expectException(HexMakina\LeMarchand\ContainerException::class);
        $this->assertNull($this->factory->serve('invalid'));
    }
}
