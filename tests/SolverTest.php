<?php

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use HexMakina\LeMarchand\Solver;
use HexMakina\LeMarchand\Factory;
use HexMakina\LeMarchand\NotFoundException;



class SolverTest extends TestCase
{
    private $container;
    private $solver;

    protected function setUp(): void
    {
        $this->solver = new Solver($this->container);
    }

}