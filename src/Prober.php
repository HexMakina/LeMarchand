<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class Prober
{
    private ContainerInterface $container;
    private Configuration $configuration;
    private Cascader $cascader;
    private Factory $factory;

    public function __construct(Configuration $conf, Cascader $cas)
    {
        $this->configuration = $conf;
        $this->cascader = $cas;

        $this->container = $this->configuration->container();
        $this->factory = new Factory($this->container);
    }

    public function probeSettings($settings)
    {
        if (!$this->configuration->isSettings()) {
            return null;
        }

        //dot based hierarchy, parse and climb
        foreach (explode('.', $this->configuration->id()) as $k) {
            if (!isset($settings[$k])) {
                throw new NotFoundException($this->configuration->id());
            }
            $settings = $settings[$k];
        }

        return $settings;
    }

    public function probeClasses($construction_args = [])
    {
        return $this->configuration->isExistingClass()
          ? $this->factory->serve($this->configuration->id(), $construction_args)
          : null;
    }

    public function probeInterface($wires)
    {
        if (!$this->configuration->isInterface()) {
            return null;
        }

        if (!isset($wires[$this->configuration->id()])) {
            throw new NotFoundException($this->configuration->id());
        }

        $wire = $wires[$this->configuration->id()];

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

    public function probeCascade()
    {
        $class_name = $this->configuration->rxModelOrController();

        if (is_null($class_name)) {
            return null;
        }

        $class_name = $this->cascader->cascadeNamespace($class_name);

        if ($this->configuration->hasClassNameModifier()) {
            $ret = $class_name;
        } elseif ($this->configuration->hasNewInstanceModifier()) {
            $ret = $this->factory->build($class_name, []);
        } else {
            $ret = $this->factory->serve($class_name, []);
        }

        return $ret;
    }
}
