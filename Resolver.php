<?php

namespace HexMakina\LeMarchand;

class Resolver
{
  private $namespace_cascade = [];
  private $resolved_cache = [];

  public function __construct($namespace_cascade = []){
    $this->namespace_cascade = $namespace_cascade;
  }

  public function cascadeNamespace($class_name)
  {
      if ($this->isResolved($class_name)) {
          return $this->resolved($class_name);
      }

      // not fully namespaced, lets cascade
      foreach ($this->namespace_cascade as $ns) {
          if (class_exists($fully_namespaced = $ns . $class_name)) {
              $this->resolved($class_name, $fully_namespaced);
              return $fully_namespaced;
          }
      }
      throw new NotFoundException($class_name);
  }


  public function resolved($clue, $solution = null)
  {
      if (!is_null($solution)) {
          $this->resolved_cache[$clue] = $solution;
      }

      return $this->resolved_cache[$clue] ?? null;
  }

  public function isResolved($clue): bool
  {
      return isset($this->resolved_cache[$clue]);
  }
}
