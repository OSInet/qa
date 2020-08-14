<?php

declare(strict_types = 1);

namespace Drupal\qa\System;

/**
 * Project represents the weakly defined project structure for an extension.
 */
class Project {
  /**
   * The list of descriptions for the modules in the project.
   *
   * @var \Drupal\qa\System\Module[]
   */
  public $modules = [];

  /**
   * The project name.
   *
   * @var string
   */
  public $name;

  /**
   * Project constructor.
   *
   * @param string $name
   *   The project name.
   */
  public function __construct(string $name) {
    $this->name = $name;
  }

  /**
   * Add a module description to the project.
   *
   * @param \Drupal\qa\System\Module $module
   *   The description of the module to add.
   */
  public function addModule(Module $module) {
    $this->modules[$module->name] = $module;
  }

  /**
   * Sort projects within the module.
   */
  public function sort() {
    ksort($this->modules);
  }

  /**
   * Is the project required ?
   *
   * @return false
   *   Is it ?
   */
  public function isRequired() {
    return FALSE;
  }

  /**
   * Count the enabled modules in the project.
   *
   * @return int
   *   The count.
   */
  public function useCount() {
    $count = 0;

    foreach ($this->modules as $module) {
      if ($module->isEnabled()) {
        $count++;
      }
    }
    return $count;
  }

}
