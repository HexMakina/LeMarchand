<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class Solver
{
    public const RX_SETTINGS = '/^settings\./';

    public const RX_INTERFACE = '/([a-zA-Z]+)Interface$/';

    public const RX_MVC = '/(Models|Controllers)\\\([a-zA-Z\\\]+)(::class|::new)?$/';

    private static array $cascade_cache = [];

    private ContainerInterface $container;
    private Factory $factory;


    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
        $this->factory = new Factory($this->container);
    }



    public function solve(string $lamentation)
    {
        return $this->probeSettings ($lamentation)
            ?? $this->probeClasses  ($lamentation)
            ?? $this->probeInterface($lamentation)
            ?? $this->probeCascade  ($lamentation);
    }

    //dot based hierarchy, parse and climb
    public function probeSettings(string $lamentation)
    {
        if (!self::isSettings($lamentation)) {
            return null;
        }

        $settings = $this->container->get('settings') ?? [];

        $path = explode('.', $lamentation);
        array_shift($path); // remove 'settings' from the path
        $walked = [];
        foreach ($path as $k) {
            
            $walked []= $k;
            
            if (isset($settings[$k])) {
                $settings = $settings[$k];
                continue;
            }

            // we didn't continue; we failed
            $walked = 'settings.' . implode('.', $walked);
            throw new NotFoundException(__FUNCTION__."($lamentation) failed at $walked");
        }

        return $settings;
    }

    public function probeClasses(string $className, array $construction_args = []): ?object
    {
        if(class_exists($className))
            return $this->factory->serve($className, $construction_args);

        return null;
    }

    public function probeInterface(string $lamentation): ?object
    {
        if (!self::isInterface($lamentation)) {
            return null;
        }

        $wires = $this->container->get('wiring') ?? [];

        if (!isset($wires[$lamentation]))
            throw new NotFoundException(__FUNCTION__."($lamentation) is not wired to a class");

        $wire = $wires[$lamentation];

        // interface + constructor params

        if (is_array($wire)) { // hasEmbeddedConstructorParameters, [0] is class, then args
            $class = array_shift($wire);
            $args = $wire;
        } else { // simple instantiation
            $class = $wire;
            $args = null;
        }

        return $this->factory->serve($class, $args);
    }

    public function probeCascade(string $lamentation)
    {
        $ret = null;

        $m = [];
        if (preg_match(self::RX_MVC, $lamentation, $m) !== 1) {
            return null;
        }
        
        $class_name = $m[1] . '\\' . $m[2];

        $class_name = $this->cascadeNamespace($class_name);

        if(is_null($class_name))
            $ret = null;
        elseif (self::hasClassNameModifier($lamentation)) {
            $ret = $class_name;
        } elseif (self::hasNewInstanceModifier($lamentation)) {
            $ret = $this->factory->build($class_name, []);
        } else {
            $ret = $this->factory->serve($class_name, []);
        }

        return $ret;
    }

    private function cascadeNamespace(string $class_name): ?string
    {
        // is it cached ?
        if (isset(self::$cascade_cache[$class_name])) {
            return self::$cascade_cache[$class_name];
        }

        // no cache lets cascade
        $cascade = $this->container->get('cascade') ?? [];

        foreach ($cascade as $namespace) {

            $fully_namespaced = $namespace . $class_name;
            
            if (class_exists($fully_namespaced)) {

                self::$cascade_cache[$class_name] = $fully_namespaced;

                return $fully_namespaced;
            }
        }

        return null;
    }

    private static function isSettings($lamentation): bool
    {
        return preg_match(self::RX_SETTINGS, $lamentation) === 1;
    }

    private static function isInterface($lamentation): bool
    {
        return preg_match(self::RX_INTERFACE, $lamentation) === 1;
    }

    private static function hasClassNameModifier($lamentation)
    {
        return strpos($lamentation, '::class') !== false;
    }

    private static function hasNewInstanceModifier($lamentation)
    {
        return strpos($lamentation, '::new') !== false;
    }
}