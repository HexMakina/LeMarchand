<?php

namespace HexMakina\LeMarchand;

use Psr\Container\{ContainerInterface, ContainerExceptionInterface, NotFoundExceptionInterface};

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
        if (isset($dbg['configurations']['template_engine'])) {
          // way too long of an object
            $dbg['configurations']['template_engine'] = get_class($dbg['configurations']['template_engine']);
        }
        return $dbg;
    }

    public function register($configuration, $instance)
    {
        $this->configurations[$configuration] = $instance;
    }

    public function has($configuration)
    {
      try{
        $this->get($configuration);
        return true;
      }
      catch(NotFoundExceptionInterface $e){
        return false;
      }
      catch(ContainerExceptionInterface $e){
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
        if (preg_match('/^settings\./', $configuration, $m) === 1) {
            return $this->getSettings($configuration);
        }
        // 3. is it a controller ?
        if (preg_match('/.+Controller$/', $configuration, $m) === 1) {
            $class_name = $this->cascadeNamespace($configuration, 'Controllers');
            return $this->getInstance($class_name);
            // return $this->cascadeControllers($configuration);
        }
        // 4. is it a class ?
        if (preg_match('/(.+)(Class|Model)$/', $configuration, $m) === 1) {
            $class_name = $this->cascadeNamespace($m[1], 'Models');
            if($m[2] === 'Model')
              return $this->getInstance($class_name);
            else
              return $class_name;
        }

        if (preg_match('/.+Interface$/', $configuration, $m) === 1) {
            $wire = $this->get('settings.interface_implementations');
            if(isset($wire[$configuration]))
              return $this->getInstance($wire[$configuration]);
        }

        throw new ConfigurationException($configuration);
    }

    // private function cascadeControllers($controller_name)
    // {
    //     // is the controller name already instantiable ?
    //     if (!is_null($instance = $this->getInstance($controller_name))) {
    //         return $instance;
    //     }
    //     // not fully namespaced, lets cascade
    //     foreach ($this->getSettings('settings.controller_namespaces') as $cns) {
    //         if (!is_null($instance = $this->getInstance($cns . $controller_name))) {
    //             return $instance;
    //         }
    //     }
    //     throw new ConfigurationException($controller_name);
    // }

    private function cascadeNamespace($class_name, $mvc_type=null)
    {
        // is the controller name already instantiable ?
        if(is_null($mvc_type) && class_exists($class_name))
          return $class_name;

        if($mvc_type !== 'Models' && $mvc_type !== 'Controllers'){
            throw new LamentException('MVC_TYPE ('.$mvc_type.') UNKOWN');
        }

        // not fully namespaced, lets cascade
        foreach ($this->getSettings('settings.namespaces') as $ns) {
            if(class_exists($full_name = $ns . $mvc_type . '\\' . $class_name))
              return $full_name;
        }
        throw new ConfigurationException($class_name);
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
