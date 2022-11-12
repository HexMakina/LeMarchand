<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class Factory
{
    private static $instance_cache = [];

    private $container = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function serve($class, $construction_args = null)
    {
        return $this->stock($class)
            ?? $this->build($class, $construction_args)
            ?? null;
    }

    public function stock($class, $instance = null)
    {
        if(!is_null($instance))
          self::$instance_cache[$class] = $instance;

        return self::$instance_cache[$class] ?? null;
    }

    public function build($class, $construction_args = null)
    {
      try {
          $reflection = new \ReflectionClass($class);
          $constructor = $reflection->getConstructor();

          $instance = null;

          if(is_null($construction_args) || is_null($constructor))
              $instance = $reflection->newInstanceArgs();
          else{

              if (empty($construction_args)) {
                  $construction_args = $this->getConstructorParameters($constructor);
              }

              $instance = $constructor->isPrivate()
                        ? $this->buildSingleton($reflection, $construction_args)
                        : $reflection->newInstanceArgs($construction_args);
          }

          $this->stock($class, $instance);

          return $instance;

      } catch (\ReflectionException $e) {
          throw new ContainerException($e->getMessage());
      }
    }


    private function getConstructorParameters(\ReflectionMethod $constructor)
    {
        $ret = [];
        foreach ($constructor->getParameters() as $param) {
            $id = $param->getType()
                  ? $param->getType()->getName()
                  : 'settings.Constructor.' . $constructor->class . '.' . $param->getName();

            $ret []= $this->container->get($id);

        }
        return $ret;
    }

    private function buildSingleton(\ReflectionClass $rc, $construction_args)
    {
      // first argument is the instantiation method
      $singleton_method = $rc->getMethod(array_shift($construction_args));

      // second are the invocation args
      $construction_args = array_shift($construction_args);

      // invoke the method with args
      $singleton = $singleton_method->invoke(null, $construction_args);

      return $singleton;
    }
}
