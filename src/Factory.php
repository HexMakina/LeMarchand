<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class Factory 
{
    // store cached instances
    private static $instance_cache = [];

    // Private container used for dependency injection
    private $container = null;

    // Constructor accepting Container Interface
    public function __construct(ContainerInterface $container)
    {
        // Assign given container instance to member variable
        $this->container = $container;
    }

    // Function accepting class name and contruction args for builder methods
    public function serve($class, $construction_args = null)
    {
        // Look for cached instance, otherwise try to build new instance
        return $this->stock($class)
            ?? $this->build($class, $construction_args)
            ?? null;
    }

    // Function to store cache instances. Also accepts single instance
    public function stock($class, $instance = null)
    {
        // If an instance was passed in, store it
        if (!is_null($instance)) {
            self::$instance_cache[$class] = $instance;
        }

        // Return  the instance found in the cache or null if not found
        return self::$instance_cache[$class] ?? null;
    }

    // Function to build new Instances from classes 
    public function build($class, $construction_args = null)
    {
        try {
            // Create ReflectionClass based on given class
            $reflection = new \ReflectionClass($class);
            
            // Get constructor from ReflectionClass
            $constructor = $reflection->getConstructor();
            $instance = null;

            // Create instance depending on existence of constructor
            if (is_null($constructor)) {
                $instance = $reflection->newInstanceArgs();
            } else {
                // Check if construction args are empty, 
                // if so get them from constructor
                if (empty($construction_args)) {
                    $construction_args = $this->getConstructorParameters($constructor);
                }

                // Determine how to build instance based on visibility of constructor
                $instance = $constructor->isPrivate()
                    ? $this->buildSingleton($reflection, $construction_args)
                    : $reflection->newInstanceArgs($construction_args);
            }

            // Cache instance for later use
            $this->stock($class, $instance);

            // Return Created Instance
            return $instance;
        } catch (\ReflectionException $e) {
            // Throw an exception based on the ReflectionException message
            throw new ContainerException($e->getMessage());
        }
    }

    // Function to get Constructor Parameters from given constructor 
    private function getConstructorParameters(\ReflectionMethod $constructor)
    {
        // Init return array
        $ret = [];
        
        foreach ($constructor->getParameters() as $param) {
            // Get parameter type, if exists
            $id = $param->getType()
                ? $param->getType()->getName()
                : 'settings.Constructor.' . $constructor->class . '.' . $param->getName();
            
            // Get resource with Id and append to return array
            $ret [] = $this->container->get($id);
        }

        // Return array with constructed objects
        return $ret;
    }

    // Function to build singletons
    private function buildSingleton(\ReflectionClass $rc, $construction_args)
    {
        // Get first argument as instantiation method 
        $singleton_method = $rc->getMethod(array_shift($construction_args));

        // Get second argument as invocation args
        $construction_args = array_shift($construction_args);

        // Invoke method with arguments
        $singleton = $singleton_method->invoke(null, $construction_args);

        // Return singletion
        return $singleton;
    }
}
