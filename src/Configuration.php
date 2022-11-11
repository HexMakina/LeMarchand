<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Configuration
{
    public const RX_SETTINGS = '/^settings\./';

    public const RX_INTERFACE = '/([a-zA-Z]+)Interface$/';

    public const RX_MVC = '/(Models|Controllers)\\\([a-zA-Z\\\]+)(::class|::new)?$/';


    private $lament;
    private ContainerInterface $box;

    public function __construct($id, ContainerInterface $c)
    {
        $this->lament = $id;
        $this->box = $c;
    }

    public function __toString()
    {
        return $this->lament;
    }

    public function id() : string
    {
      return $this->lament;
    }

    public function container() : ContainerInterface
    {
      return $this->box;
    }

    public function resolver() : Resolver
    {
      return $this->box->resolver();
    }

    public function isSettings() : bool
    {
      return preg_match(self::RX_SETTINGS, $this->lament) === 1;
    }

    public function isExistingClass() : bool
    {
      return class_exists($this->lament);
    }

    public function isInterface() : bool
    {
      return preg_match(self::RX_INTERFACE, $this->lament) === 1;
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

    public function hasClassNameModifier()
    {
        return strpos($this->lament, '::class') !== false;
    }

    public function hasNewInstanceModifier()
    {
        return strpos($this->lament, '::new') !== false;
    }

}
