<?php

namespace HexMakina\LeMarchand;

class Prober
{
    private Configuration $configuration;
    private Cascader $resolver;

    public function __construct(Configuration $c, Cascader $r)
    {
        $this->configuration = $c;
        $this->resolver = $r;
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
          ? ReflectionFactory::get($this->configuration->id(), $construction_args, $this->configuration->container(), $this->resolver)
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


        if (
            $this->resolver->isResolved($class)
            && ReflectionFactory::hasPrivateContructor($class)
        ) {
            return $this->resolver->resolved($class);
        }

        return ReflectionFactory::get($class, $args, $this->configuration->container(), $this->resolver);
    }

    public function probeCascade()
    {
        $class_name = $this->configuration->rxModelOrController();

        if (is_null($class_name)) {
            return null;
        }

        $class_name = $this->resolver->cascadeNamespace($class_name);

        if ($this->configuration->hasClassNameModifier()) {
            $ret = $class_name;
        } elseif ($this->configuration->hasNewInstanceModifier()) {
            $ret = ReflectionFactory::make($class_name, [], $this->configuration->container(), $this->resolver);
        } else {
            $ret = ReflectionFactory::get($class_name, [], $this->configuration->container(), $this->resolver);
        }

        return $ret;
    }
}
