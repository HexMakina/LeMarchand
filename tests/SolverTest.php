<?php

use PHPUnit\Framework\TestCase;

use HexMakina\LeMarchand\Solver;
use HexMakina\LeMarchand\LeMarchand;

class SolverTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        $this->container = new LeMarchand([]);
    }
    
    public function testEmptyConstructor()
    {
        $res = new Solver($this->container);
        $this->assertInstanceOf(Solver::class, $res);
    }

}