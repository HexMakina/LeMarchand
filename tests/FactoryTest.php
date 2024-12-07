<?php
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use HexMakina\LeMarchand\Factory;

class FactoryTest extends TestCase
{
    private $container;
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new Factory($this->container);
    }

}