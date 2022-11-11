<?php

namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LeMarchand implements ContainerInterface
{
    private static $instance = null;

    private $configurations = [];

    private $interface_wiring = [];

    private $resolver = null;

    public static function box($settings = null): ContainerInterface
    {
        if (is_null(self::$instance)) {
            if (is_array($settings)) {
                return (self::$instance = new LeMarchand($settings));
            }
            throw new ContainerException('UNABLE_TO_OPEN_BOX');
        }

        return self::$instance;
    }


    private function __construct($settings)
    {
        if (isset($settings[__CLASS__])) {
            $this->resolver = new Resolver($settings[__CLASS__]['cascade'] ?? []);
            $this->interface_wiring = $settings[__CLASS__]['wiring'] ?? [];
            unset($settings[__CLASS__]);
        }

        $this->configurations['settings'] = $settings;
    }

    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);

        foreach ($dbg['interface_wiring'] as $interface => $wire) {
            if (is_array($wire)) {
                $wire = array_shift($wire) . ' --array #' . count($wire);
            }
            $dbg['interface_wiring'][$interface] = $wire;
        }

        return $dbg;
    }

    public function resolver() : Resolver
    {
      return $this->resolver;
    }

    public function has($id)
    {
        try {
            $this->get($id);
            return true;
        } catch (NotFoundExceptionInterface $e) {
            return false;
        } catch (ContainerExceptionInterface $e) {
            return false;
        }
        return false;
    }


    public function get($id)
    {
        if (!is_string($id)) {
            throw new ContainerException($id);
        }

        if (isset($this->configurations[$id])) {
            return $this->configurations[$id];
        }

        // not a simple configuration string, it has meaning
        $configuration = new Configuration($id, $this);
        $prober = new Prober($configuration);

        $res = $prober->probeSettings($this->configurations)
            ?? $prober->probeClasses()
            ?? $prober->probeInterface($this->interface_wiring)
            ?? $prober->probeCascade();

        if(is_null($res))
            throw new NotFoundException($id);

        return $res;
    }
}
