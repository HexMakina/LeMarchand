<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Configuration
{
    public const RX_SETTINGS = '/^settings\./';

    // public const RX_MVC = '/(Models|Controllers)\\\([a-zA-Z]+)(::class|::new)?/';
    public const RX_MVC = '/(Models|Controllers)\\\([a-zA-Z\\\]+)(::class|::new)?$/';

    public const RX_INTERFACE = '/([a-zA-Z]+)Interface$/';

    private $lament;
    private ContainerInterface $box;

    public function __construct($configuration_string, ContainerInterface $c)
    {
        $this->lament = $configuration_string;
        $this->box = $c;
    }

    public function __toString()
    {
        return $this->lament;
    }

    public function resolver()
    {
      return $this->box->resolver();
    }

    public function classExists() : bool
    {
      return class_exists($this->lament);
    }

    public function isSettings() : bool
    {
        return preg_match(self::RX_SETTINGS, $this->lament) === 1;
    }

    public function isInterface() : bool
    {
        return preg_match(self::RX_INTERFACE, $this->lament) === 1;
    }

    public function probeSettings($settings)
    {
        if (!$this->isSettings())
          return null;

      //dot based hierarchy, parse and climb
        foreach (explode('.', $this->lament) as $k) {
            if (!isset($settings[$k])) {
                throw new NotFoundException($setting);
            }
            $settings = $settings[$k];
        }

        return $settings;
    }

    public function probeClasses($construction_args = [])
    {
        if(!$this->classExists())
            return null;

        return ReflectionFactory::get($this->lament, $construction_args, $this->box);
    }


    public function probeInterface($wires)
    {
        if (!$this->isInterface())
            return null;

        if (!isset($wires[$this->lament])) {
            throw new NotFoundException($this->lament);
        }

        $wire = $wires[$this->lament];

        // interface + constructor params

        if (is_array($wire)) { // hasEmbeddedConstructorParameters, [0] is class, then args
            $class = array_shift($wire);
            $args = $wire;
        } else { // simple instantiation
            $class = $wire;
            $args = null;
        }

        if ($this->resolver()->isResolved($class) && ReflectionFactory::hasPrivateContructor($class)) {
            return $this->resolver()->resolved($class);
        }

        return ReflectionFactory::get($class, $args, $this->box);
    }

    // public function isModelOrController()
    // {
    //     return preg_match(self::RX_MVC, $this->lament) === 1;
    // }

    public function probeCascade()
    {
      $class_name = $this->rxModelOrController();

      if(is_null($class_name))
          return null;

      $class_name = $this->resolver()->cascadeNamespace($class_name);

      if ($this->hasClassNameModifier()) {
          $ret = $class_name;
      } elseif ($this->hasNewInstanceModifier()) {
          $ret = ReflectionFactory::make($class_name, [], $this->box);
      } else {
          $ret = ReflectionFactory::get($class_name, [], $this->box);
      }

      return $ret;
    }


    public function rxModelOrController() : ?string
    {
        $ret = null;

        $m=[];
        if(preg_match(self::RX_MVC, $this->lament, $m) === 1){
            $ret = $m[1] . '\\' . $m[2];
        }
        else{
        }
        return $ret;
    }

    public function getModelOrControllerName()
    {
        preg_match(self::RX_MVC, $this->lament, $m);
        return $m[1] . '\\' . $m[2];
    }

    public function hasClassNameModifier()
    {
        return strpos($this->lament, '::class') !== false;
    }

    public function hasNewInstanceModifier()
    {
        return strpos($this->lament, '::new') !== false;
    }

}
