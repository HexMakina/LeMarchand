<?php

namespace HexMakina\LeMarchand;

class Configuration
{
    public const RX_SETTINGS = '/^settings\./';

    public const RX_MVC = '/(Models|Controllers)\\\([a-zA-Z]+)(::class|::new)?/';

    public const RX_INTERFACE = '/([a-zA-Z]+)Interface$/';


    private $lament;

    public function __construct($configuration_string)
    {
        $this->lament = $configuration_string;
    }

    public function __toString()
    {
        return $this->configurationString();
    }

    public function configurationString()
    {
        return $this->lament;
    }

    public function isSettings()
    {
        return preg_match(self::RX_SETTINGS, $this->lament) === 1;
    }

    public function isInterface()
    {
        return preg_match(self::RX_INTERFACE, $this->lament) === 1;
    }

    public function isModelOrController()
    {
        return preg_match(self::RX_MVC, $this->lament) === 1;
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
