<?php 
namespace HexMakina\kadro\Container;

use \Psr\Container\{NotFoundExceptionInterface};

class ConfigurationException extends \Exception implements NotFoundExceptionInterface
{
  public function __construct($configuration)
  {
    return parent::__construct("Unkown configuration '$configuration'");
  }
}

