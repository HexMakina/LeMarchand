<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;

class Prober
{
    private static array $cascade_cache = [];

    private ContainerInterface $container;
    private Configuration $configuration;
    private Factory $factory;
    private array $cascade;

    public function __construct(Configuration $conf, array $cascade = [])
    {
        $this->configuration = $conf;
        $this->cascade = $cascade;

        $this->container = $this->configuration->container();
        $this->factory = new Factory($this->container);
    }

    public function probeSettings(array $settings)
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

    public function probeClasses(array $construction_args = []): ?object
    {
        return $this->configuration->isExistingClass()
          ? $this->factory->serve($this->configuration->id(), $construction_args)
          : null;
    }

    public function probeInterface(array $wires): ?object
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

        $class_name = $this->cascadeNamespace($class_name);

        if ($this->configuration->hasClassNameModifier()) {
            $ret = $class_name;
        } elseif ($this->configuration->hasNewInstanceModifier()) {
            $ret = $this->factory->build($class_name, []);
        } else {
            $ret = $this->factory->serve($class_name, []);
        }

        return $ret;
    }


    private function cascadeNamespace(string $class_name): string
    {
        if (isset($this::$cascade_cache[$class_name])) {
            return $this::$cascade_cache[$class_name];
        }

        // not fully namespaced, lets cascade
        foreach ($this->cascade as $ns) {
            if (class_exists($fully_namespaced = $ns . $class_name)) {
                $this::$cascade_cache[$class_name] = $fully_namespaced;

                return $fully_namespaced;
            }
        }

        throw new NotFoundException($class_name);
    }
}
