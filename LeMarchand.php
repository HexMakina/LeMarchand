<?php

namespace HexMakina\LeMarchand;

use Psr\Container\{ContainerInterface, ContainerExceptionInterface, NotFoundExceptionInterface};

class LeMarchand implements ContainerInterface
{
    private static $instance = null;
    private $configurations = [];

    // public const RX_CONTROLLER_NAME = '/([a-zA-Z]+)Controller$/';
    // public const RX_MODEL_CLASS = '/([a-zA-Z]+)(Class|Model)$/';
    const RX_SETTINGS = '/^settings\./';
    // public const RX_INTERFACE_NAME = '/([a-zA-Z]+)Interface$/';
    const RX_CLASS_NAME = '/([a-zA-Z]+)(Class|Model|Controller|Interface)$/';


    public static function box($settings = null): ContainerInterface
    {
        if (is_null(self::$instance)) {
            if (is_array($settings)) {
                return (self::$instance = new LeMarchand($settings));
            }
            throw new LamentException('UNABLE_TO_OPEN_BOX');
        }

        return self::$instance;
    }


    private function __construct($settings)
    {
        $this->configurations['settings'] = $settings;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);
        if (isset($dbg['configurations']['template_engine'])) {
          // way too long of an object
            $dbg['configurations']['template_engine'] = get_class($dbg['configurations']['template_engine']);
        }
        return $dbg;
    }

    public function put($configuration, $instance)
    {
        $this->configurations[$configuration] = $instance;
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
    }


    public function get($configuration)
    {
        if (!is_string($configuration)) {
            throw new LamentException($configuration);
        }
        // 1. is it a first level key ?
        if (isset($this->configurations[$configuration])) {
            return $this->configurations[$configuration];
        }

        // 2. is it configuration data ?
        if (preg_match(self::RX_SETTINGS, $configuration, $m) === 1) {
            return $this->getSettings($configuration);
        }
        // 3. is it a class
        if (preg_match(self::RX_CLASS_NAME, $configuration, $m) === 1) {
            return $this->classification($m[1], $m[2]);
        }

        throw new ConfigurationException($configuration);
    }


    private function getSettings($setting)
    {
        $ret = $this->configurations;

      //dot based hierarchy, parse and climb
        foreach (explode('.', $setting) as $k) {
            if (!isset($ret[$k])) {
                throw new ConfigurationException($setting);
            }
            $ret = $ret[$k];
        }

        return $ret;
    }

    private function classification($name, $type)
    {
        $class_name = $this->cascadeNamespace($name, $type);

        if ($type === 'Class') {
            return $class_name;
        }

        if ($type === 'Interface') {
            return $this->wireInstance($name);
        }

        return $this->getInstance($class_name);
    }

    private function cascadeNamespace($class_name, $mvc_type = null)
    {
        // does the name already exists ?
        if (class_exists($class_name)) {
            return $class_name;
        }

        if ($mvc_type === 'Class') {
            $mvc_type = 'Model';
        }

        if ($mvc_type === 'Controller') {
            $class_name = $class_name . 'Controller';
        }

        // not fully namespaced, lets cascade
        foreach ($this->getSettings('settings.namespaces') as $ns) {
            if (class_exists($full_name = $ns . $mvc_type . 's\\' . $class_name)) {
                return $full_name;
            }
        }

        throw new ConfigurationException($class_name);
    }

    private function wireInstance($interface)
    {
        $wire = $this->getSettings('settings.interface_implementations');

        if (!isset($wire[$interface])) {
            throw new ConfigurationException($interface);
        }

        return $this->getInstance($wire[$interface]);
    }

    private function getInstance($class)
    {
        try {
            $rc = new \ReflectionClass($class);
            $instance = null;
            $construction_args = [];
            if (!is_null($rc->getConstructor())) {
                foreach ($rc->getConstructor()->getParameters() as $param) {
                    $construction_args [] = $this->get($param->getType() . '');
                }
                $instance = $rc->newInstanceArgs($construction_args);
            } else {
                $instance = $rc->newInstanceArgs();
            }

            if ($rc->hasMethod('set_container')) {
                $instance->set_container($this);
            }

            return $instance;
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
