<?php
/**
 * @file
 * Project.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014-2018 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Drupal\qa\Plugin\Qa\Control\System;


class Project {
  /**
   * @var \Drupal\qa\Plugin\Qa\Control\System\Module[]
   */
  public $modules = array();
  public $name;

  public function __construct($name)  {
    $this->name = $name;
  }

  public function addModule(Module $module) {
    $this->modules[$module->name] = $module;
  }

  public function sort() {
    ksort($this->modules);
  }

  public function isRequired() {
    return FALSE;
  }

  /**
   * How many modules in this project are actually enabled ?
   *
   * @return int
   *   The number of enabled modules.
   */
  public function useCount() {
    $count = 0;

    foreach ($this->modules as $module) {
      if ($module->isEnabled())  {
        $count++;
      }
    }
    return $count;
  }

}
