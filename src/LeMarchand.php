<?php 
namespace HexMakina\LeMarchand;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LeMarchand implements ContainerInterface
{
    private static $instance = null;

    private array $configurations; 

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
    public function __construct($settings)
    {
        $this->configurations = $settings[__CLASS__] ?? [];
        
        unset($settings[__CLASS__]);
        
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

        foreach ($dbg['configurations']['wiring'] as $interface => $wire) {
            if (is_array($wire)) {
                $wire = array_shift($wire) . ' --array #' . count($wire);
            }
            $dbg['configurations']['wiring'] = $wire;
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
    // This method gets an item from the container based on its ID
    public function get($id)
    {
        // Check if the ID is a string, if not, throw an exception
        if (!is_string($id) || empty($id)) {
            throw new ContainerException($id);
        }

        // Check if the ID is a simple configuration string, if so, return the configuration
        if (isset($this->configurations[$id])) {
            return $this->configurations[$id];
        }

        
        $victim = new Solver($this);
        $res = $victim->solve($id);

        // $prober = new Solver($configuration, $this->configurations['cascade'] ?? []);

        // // Try to get the item from the container by probing the settings, classes, interface wiring, and namespace cascade
        // $res = $prober->probeSettings($this->configurations)
        //     ?? $prober->probeClasses()
        //     ?? $prober->probeInterface($this->interface_wiring)
        //     ?? $prober->probeCascade();

        // If the item is not found, throw a NotFoundException
        if (is_null($res)) {
            throw new NotFoundException($id);
        }

        // Return the item
        return $res;
    }
}
