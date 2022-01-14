<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LeMarchand implements ContainerInterface
{
    private static $instance = null;
    // stores all the settings
    private $configurations = [];

    // stores the namespace cascade
    private $namespace_cascade = [];

    // stores the interface to class wiring
    private $interface_wiring = [];

    // store the resolved names for performance
    private $resolved_cache = [];

    // stores the automatically created instances, by class name
    private $instance_cache = [];


    public static function box($settings = null): ContainerInterface
    {
        if (is_null(self::$instance)) {
            if (is_array($settings)) {
                return (self::$instance = new LeMarchand($settings));
            }
            throw new ContainerException('UNABLE_TO_OPEN_BOX');
        }

        return self::$instance;
    }


    private function __construct($settings)
    {
        if (isset($settings[__CLASS__])) {
            $this->namespace_cascade = $settings[__CLASS__]['cascade'] ?? [];
            $this->interface_wiring = $settings[__CLASS__]['wiring'] ?? [];
            unset($settings[__CLASS__]);
        }
        $this->configurations['settings'] = $settings;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);

        foreach ($dbg['instance_cache'] as $class => $instance) {
            $dbg['instance_cache'][$class] = true;
        }

        foreach ($dbg['interface_wiring'] as $interface => $wire) {
            if (is_array($wire)) {
                $wire = array_shift($wire) . ' --array #' . count($wire);
            }
            $dbg['interface_wiring'][$interface] = $wire;
        }

        return $dbg;
    }

    public function has($configuration)
    {
        try {
            $this->get($configuration);
            return true;
        } catch (NotFoundExceptionInterface $e) {
            return false;
        } catch (ContainerExceptionInterface $e) {
            return false;
        }
        return false;
    }


    public function get($configuration_string)
    {
        if (!is_string($configuration_string)) {
            throw new ContainerException($configuration_string);
        }

        $ret = null;


        if ($this->isFirstLevelKey($configuration_string)) {
            return $this->configurations[$configuration_string];
        }

        // not a simple configuration string, it has meaning
        $configuration = new Configuration($configuration_string);
        if ($configuration->isSettings()) {
            $ret = $this->getSettings($configuration);
        } elseif (class_exists($lament)) {
            $ret = $this->getInstance($configuration);
        } elseif ($configuration->isInterface()) {
            $ret = $this->wireInstance($configuration);
        } elseif ($configuration->hasModelOrController()) {
            // 5. is it cascadable ?

            $class_name = $configuration->getModelOrControllerName();
            $class_name = $this->cascadeNamespace($class_name);

            if ($configuration->hasClassNameModifier()) {
                $ret = $class_name;
            } elseif ($configuration->hasNewInstanceModifier()) {
                $ret = $this->makeInstance($class_name);
            }
            $ret = $this->getInstance($class_name);
        }

        if (is_null($ret)) {
            throw new NotFoundException($configuration);
        }

        return $ret;
    }

    public function isFirstLevelKey($configuration_string)
    {
        return isset($this->configurations[$configuration_string]);
    }

    private function getSettings($setting)
    {
        // vd(__FUNCTION__);
        $ret = $this->configurations;

      //dot based hierarchy, parse and climb
        foreach (explode('.', $setting) as $k) {
            if (!isset($ret[$k])) {
                throw new NotFoundException($setting);
            }
            $ret = $ret[$k];
        }

        return $ret;
    }

    private function resolved($clue, $solution = null)
    {
        if (!is_null($solution)) {
            $this->resolved_cache[$clue] = $solution;
        }
        // vd($clue, __FUNCTION__);
        return $this->resolved_cache[$clue] ?? null;
    }

    private function isResolved($clue): bool
    {
        return isset($this->resolved_cache[$clue]);
    }

    private function cascadeNamespace($class_name)
    {
        if ($this->isResolved($class_name)) {
            return $this->resolved($class_name);
        }

        // not fully namespaced, lets cascade
        foreach ($this->namespace_cascade as $ns) {
            if (class_exists($fully_namespaced = $ns . $class_name)) {
                $this->resolved($class_name, $fully_namespaced);
                return $fully_namespaced;
            }
        }
        throw new NotFoundException($class_name);
    }

    private function wireInstance($interface)
    {
        if (!isset($this->interface_wiring[$interface])) {
            throw new NotFoundException($interface);
        }

        $wire = $this->interface_wiring[$interface];

        // interface + constructor params
        if ($this->hasEmbeddedConstructorParameters($wire)) {
            $class = array_shift($wire);
            $args = $wire;
        } else {
            $class = $wire;
            $args = null;
        }

        if ($this->isResolved($class) && $this->hasPrivateContructor($class)) {
            return $this->resolved($class);
        }

        return $this->getInstance($class, $args);
    }

    private function hasPrivateContructor($class_name): bool
    {
        $rc = new \ReflectionClass($class_name);
        return !is_null($constructor = $rc->getConstructor()) && $constructor->isPrivate();
    }

    private function hasEmbeddedConstructorParameters($wire)
    {
        return is_array($wire);
    }

    private function getInstance($class, $construction_args = [])
    {
        if (isset($this->instance_cache[$class])) {
            return $this->instance_cache[$class];
        }

        return $this->makeInstance($class, $construction_args);
    }

    private function makeInstance($class, $construction_args = [])
    {
        try {
            $rc = new \ReflectionClass($class);
            $instance = null;

            if (!is_null($constructor = $rc->getConstructor())) {
                $construction_args = $this->getConstructorParameters($constructor, $construction_args);

                if ($constructor->isPrivate()) { // singleton ?
                  // first argument is the static instance-making method
                    $singleton_method = $rc->getMethod(array_shift($construction_args));
                  // invoke the method with remaining constructor args
                    $instance = $this->resolved($class, $singleton_method->invoke(null, $construction_args));
                } else {
                    $instance = $rc->newInstanceArgs($construction_args);
                }
            } else {
                $instance = $rc->newInstanceArgs();
            }

            if ($rc->hasMethod('set_container')) {
                $instance->set_container($this);
            }
            $this->instance_cache[$class] = $instance;

            return $instance;
        } catch (\ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }
    }

    private function getConstructorParameters(\ReflectionMethod $constructor, $construction_args = [])
    {
      // vd(__FUNCTION__);

        if (empty($construction_args)) {
            foreach ($constructor->getParameters() as $param) {
                // try {
                if ($param->getType()) {
                    $construction_args [] = $this->get($param->getType()->getName());
                } else {
                    $setting = 'settings.Constructor.' . $constructor->class . '.' . $param->getName();
                    $construction_args [] = $this->getSettings($setting);
                }
                // } catch (NotFoundExceptionInterface $e) {
                //     dd($e);
                // }
            }
        }
        return $construction_args;
    }
}
