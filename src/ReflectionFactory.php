<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class ReflectionFactory
{
    private static $instance_cache = [];

    public static function get($class, $construction_args, ContainerInterface $container, Cascader $resolver)
    {
        return ReflectionFactory::getCacheFor($class)
            ?? ReflectionFactory::make($class, $construction_args, $container, $resolver) ?? null;
    }

    public static function make($class, $construction_args, ContainerInterface $container, Cascader $resolver)
    {
        try {
            $rc = new \ReflectionClass($class);
            $instance = null;

            if (!is_null($constructor = $rc->getConstructor())) {
                $instance = self::makeWithContructorArgs($rc, $construction_args, $container, $resolver);
            } else {
                $instance = $rc->newInstanceArgs();
            }

            // if ($rc->hasMethod('set_container')) {
            //     $instance->set_container($container);
            // }

            self::setCacheFor($class, $instance);

            return $instance;
        } catch (\ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }
    }


    public static function hasPrivateContructor($class_name): bool
    {
        $rc = new \ReflectionClass($class_name);
        return !is_null($constructor = $rc->getConstructor()) && $constructor->isPrivate();
    }


    private static function getCacheFor($class)
    {
        return self::$instance_cache[$class] ?? null;
    }

    private static function setCacheFor($class, $instance)
    {
        self::$instance_cache[$class] = $instance;
    }

    private static function makeWithContructorArgs(
        \ReflectionClass $rc,
        $construction_args,
        ContainerInterface $container,
        Cascader $r
    ) {
        $constructor = $rc->getConstructor();

        if (empty($construction_args)) {
            $construction_args = self::getConstructorParameters($constructor, $container);
        }

        $instance = null;
        if ($constructor->isPrivate()) { // singleton ?
          // first argument is the static instance-making method
            $singleton_method = $rc->getMethod(array_shift($construction_args));
            $construction_args = array_shift($construction_args);
            // invoke the method with remaining constructor args
            $instance = $r->resolved(
                $rc->getName(),
                $singleton_method->invoke(null, $construction_args)
            );
        } else {
            $instance = $rc->newInstanceArgs($construction_args);
        }

        return $instance;
    }

    private static function getConstructorParameters(\ReflectionMethod $constructor, ContainerInterface $container)
    {
        $ret = [];
        foreach ($constructor->getParameters() as $param) {
            $id = $param->getType()
                  ? $param->getType()->getName()
                  : 'settings.Constructor.' . $constructor->class . '.' . $param->getName();

            $ret []= $container->get($id);

        }
        return $ret;
    }
}
