<?php
/**
 * @file
 * Project.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace OSInet\DrupalQA\System;


class Project {
  /**
   * @var \OSInet\DrupalQA\System\Module[]
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
