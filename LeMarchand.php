<?php 
 
namespace HexMakina\kadro\Container;

use \Psr\Container\ContainerInterface;

class LeMarchand implements ContainerInterface
{
  private $configurations = [];
  
  public function __construct($settings)
  {
    $this->configurations['settings'] = $settings;
  }
  
  public function __debugInfo() : array
  {
    $dbg = get_object_vars($this);
    $dbg['configurations']['template_engine'] = isset($dbg['configurations']['template_engine'])? get_class($dbg['configurations']['template_engine']) : 'NOT SET';

    return $dbg;
  }
  
  public function register($configuration, $instance)
  {
    $this->configurations[$configuration] = $instance;
  }
  
  public function has($configuration)
  {
    return isset($this->configurations[$configuration]);
  }
  
  public function get($configuration)
  {
    
    if(!is_string($configuration))
      throw new LamentException($configuration);

    if($this->has($configuration))
      return $this->configurations[$configuration];

    
    // fallbacks
    if(preg_match('/^settings\./', $configuration, $m) === 1)
    {
      $ret = $this->configurations;
      foreach(explode('.', $configuration) as $k)
      {
        if(!isset($ret[$k]))
          throw new ConfigurationException($configuration);
        $ret = $ret[$k];
      }

      return $ret;
    }
    elseif(class_exists($configuration)) // auto create instances
    {
      
      $rc = new \ReflectionClass($configuration);

      $construction_args = [];
      $instance = null;
      if(!is_null($rc->getConstructor()))
      {
        foreach($rc->getConstructor()->getParameters() as $param)
        {
          $construction_args []= $this->get($param->getType().'');
        }
        $instance = $rc->newInstanceArgs($construction_args);
      }
      else 
        $instance = $rc->newInstanceArgs();

      if($rc->hasMethod('set_container'))
        $instance->set_container($this);
      
      return $instance;
    }

    throw new ConfigurationException($configuration);
  }

}


