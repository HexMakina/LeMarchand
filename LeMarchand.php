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
      // 2. creating instances

        if(preg_match('/^settings\./', $configuration, $m) === 1)
        {
          return $this->get_settings($configuration);
        }
        elseif(preg_match('/.+Controller$/', $configuration, $m) === 1)
        {
          foreach($this->get_settings('settings.controllers_namespaces') as $controller_namespace)
          {
            if(class_exists($controller_namespace.$configuration))
              return $this->get_instance($controller_namespace.$configuration);
          }
        }

        throw new ConfigurationException($configuration);
    }

    private function get_instance($class)
    {
        $rc = new \ReflectionClass($class);

        $construction_args = [];
        $instance = null;
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


    private function get_settings($setting)
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
