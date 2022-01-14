<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LeMarchand implements ContainerInterface
{
    private static $instance = null;

    private $configurations = [];

    private $interface_wiring = [];

    private $instance_cache = [];

    private $resolver = null;

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
            $this->resolver = new Resolver($settings[__CLASS__]['cascade'] ?? []);
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

        if ($this->isFirstLevelKey($configuration_string)) {
            return $this->configurations[$configuration_string];
        }

        // not a simple configuration string, it has meaning
        $res = $this->getComplexConfigurationString($configuration_string);

        if (!is_null($res)) {
            throw new NotFoundException($configuration_string);
        }

        return $res;
    }

    private function getComplexConfigurationString($configuration_string)
    {
        $configuration = new Configuration($configuration_string);

        $ret = null;

        if ($configuration->isSettings()) {
            $ret = $this->getSettings($configuration);
        } elseif (class_exists($configuration_string)) {
            $ret = $this->getInstance($configuration);
        } elseif ($configuration->isInterface()) {
            $ret = $this->wireInstance($configuration);
        } elseif ($configuration->isModelOrController()) {
            $ret = $this->cascadeInstance($configuration);
        }

        return $ret;
    }

    private function isFirstLevelKey($configuration_string)
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

    private function cascadeInstance(Configuration $configuration)
    {
        $class_name = $configuration->getModelOrControllerName();
        $class_name = $this->resolver->cascadeNamespace($class_name);

        if ($configuration->hasClassNameModifier()) {
            $ret = $class_name;
        } elseif ($configuration->hasNewInstanceModifier()) {
            $ret = $this->makeInstance($class_name);
        }
        else{
          $ret = $this->getInstance($class_name);
        }

        return $ret;
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

        if ($this->resolver->isResolved($class) && $this->hasPrivateContructor($class)) {
            return $this->resolver->resolved($class);
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
        $instance = ReflectionFactory::make($class, $construction_args, $this);
        $this->instance_cache[$class] = $instance;
        return $instance;
    }
}
