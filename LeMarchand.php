<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class LeMarchand implements ContainerInterface
{
    private $configurations = [];

    public function __construct($settings)
    {
        $this->configurations['settings'] = $settings;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);
        $dbg['configurations']['template_engine'] = isset($dbg['configurations']['template_engine']) ? get_class($dbg['configurations']['template_engine']) : 'NOT SET';

        return $dbg;
    }

    public function register($configuration, $instance)
    {
        $this->configurations[$configuration] = $instance;
    }

    public function has($configuration)
    {
        return isset($this->configurations[$configuration]);
    }


    public function get($configuration)
    {
        if (!is_string($configuration)) {
            throw new \InvalidArgumentException($configuration);
        }

        if ($this->has($configuration)) {
            return $this->configurations[$configuration];
        }

        // fallbacks
        // 1. configuration data
        if (preg_match('/^settings\./', $configuration, $m) === 1) {
            return $this->getSettings($configuration);
        }
        // 2. creating instances
        if (preg_match('/.+Controller$/', $configuration, $m) === 1) {
            return $this->cascadeControllers($controller_name);
        }

        throw new ConfigurationException($configuration);
    }

    private function cascadeControllers($controller_name)
    {
      foreach ($this->getSettings('settings.controllers_namespaces') as $cns) {
          if (!is_null($instance = $this->getInstance($cns . $controller_name))) {
              return $instance;
          }
      }
      throw new ConfigurationException($controller_name);
    }

    private function getInstance($class)
    {
      try{
        $rc = new \ReflectionClass($class);
        $instance=null;
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
      }
      catch(\ReflectionException $e)
      {
        return null;
      }
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
}
