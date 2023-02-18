<?php 
namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LeMarchand implements ContainerInterface
{
    private static $instance = null;

    private $configurations = []; // An array for the configurations 

    private $interface_wiring = []; // An array for the wiring of the interface 

    private $namespace_cascade = []; // An array for the namespace  cascade 

    /**
     * Get a container instance  
     * 
     * @param array|null $settings // The container settings
     * 
     * @return ContainerInterface
     * 
     * @throws ContainerException
     */ 
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

    /**
     * Construct a new instance of LeMarchand 
     * 
     * @param array $settings // The settings of LeMarchand
     */
    private function __construct($settings)
    {
        if (isset($settings[__CLASS__])) {
            $this->namespace_cascade = $settings[__CLASS__]['cascade'] ?? [];
            $this->interface_wiring = $settings[__CLASS__]['wiring'] ?? [];
            unset($settings[__CLASS__]);
        }

        $this->configurations['settings'] = $settings;
    }

    /**
     * Return information about the instance
     * 
     * @return array
     */
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

    /**
     * Check if an item is set in the container
     * 
     * @param string $id // The ID of the item 
     * 
     * @return bool
     */ 
    public function has($id)
    {
        try {
            $this->get($id);
            return true;
        } catch (NotFoundExceptionInterface $e) {
          // return false;
        } catch (ContainerExceptionInterface $e) {
          // return false;
        }

        return false;
    }

    /**
     * Get an item from the container
     *
     * @param string $id // The ID of the item 
     * 
     * @return mixed
     * 
     * @throws NotFoundExceptionInterface If the item is not found
     * @throws ContainerExceptionInterface If there is a problem with getting the item
     */
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
        $prober = new Prober($configuration, $this->namespace_cascade);

        $res = $prober->probeSettings($this->configurations)
            ?? $prober->probeClasses()
            ?? $prober->probeInterface($this->interface_wiring)
            ?? $prober->probeCascade();

        if (is_null($res)) {
            throw new NotFoundException($id);
        }

        return $res;
    }
}
