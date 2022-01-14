<?php

namespace HexMakina\LeMarchand;

class ReflectionFactory
{
    private static $instance_cache = [];

    public static function make($class, $construction_args = [], $container)
    {
        try {
            $rc = new \ReflectionClass($class);
            $instance = null;

            if (!is_null($constructor = $rc->getConstructor())) {
                $instance = self::makeWithContructorArgs($rc, $construction_args, $container);
            } else {
                $instance = $rc->newInstanceArgs();
            }

            if ($rc->hasMethod('set_container')) {
                $instance->set_container($container);
            }

            self::setCacheFor($class, $instance);

            return $instance;

        } catch (\ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }
    }

    public static function hasCacheFor($class){
        return isset(self::$instance_cache[$class])
    }

    public static function getCacheFor($class){
        return self::$instance_cache[$class];
    }

    public static function setCacheFor($class, $instance){
        self::$instance_cache[$class] = $instance;
    }

    private static function makeWithContructorArgs(\ReflectionClass $rc, $construction_args, $container)
    {
        $constructor = $rc->getConstructor();
        $construction_args = self::getConstructorParameters($constructor, $construction_args, $container);

        $instance = null;
        if ($constructor->isPrivate()) { // singleton ?
          // first argument is the static instance-making method
            $singleton_method = $rc->getMethod(array_shift($construction_args));
            // invoke the method with remaining constructor args
            $instance = $container->resolved($rc->getName(), $singleton_method->invoke(null, $construction_args));
        } else {
            $instance = $rc->newInstanceArgs($construction_args);
        }

        return $instance;
    }

    private static function getConstructorParameters(\ReflectionMethod $constructor, $construction_args = [], $container)
    {
        if (empty($construction_args)) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getType()) {
                    $construction_args [] = $container->get($param->getType()->getName());
                } else {
                    $setting = 'settings.Constructor.' . $constructor->class . '.' . $param->getName();
                    $construction_args [] = $container->getSettings($setting);
                }
            }
        }
        return $construction_args;
    }
}
