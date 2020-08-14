<?php
/**
 * @file
 * Module.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Drupal\qa\System;

use Drupal\Core\Extension\Extension;

class Module {
  public $filename;
  public $hidden = FALSE;
  public $name;
  public $info;
  public $type;
  public $status;
  public $schema_version;
  public $weight = 0;
  public $required_by = [];
  public $requires = [];
  public $sort;

  public static function createFromCore(Extension $object) {
    $o = new Module();
    $rc = new \ReflectionClass(Extension::class);

    /** @var \ReflectionProperty $p */
    foreach ($rc->getProperties() as $p) {
      $name = $p->getName();
      $p->setAccessible(TRUE);
      $value = $p->getValue($object);
      $o->{$name} = $value;
    }

    return $o;
  }

  /**
   * @return array
   *
   * FIXME very incomplete: still relies on the D7 info structure, missing most
   * properties on D8.
   */
  public static function getInfo()  {
    $module_data = system_rebuild_module_data();
    /** @var Module[] $modules */
    $modules = [];

    /** @var Project[] $projects */
    $projects = [];

    foreach ($module_data as $name => $info) {
      $module = Module::createFromCore($info);
      if ($module->isHidden()) {
        continue;
      }
      $modules[$name] = $module;
      $project_name = $module->getProject();
      if (!isset($projects[$project_name])) {
        $projects[$project_name] = new Project($project_name);
      }
      $project = $projects[$project_name];
      $project->addModule($module);
    }

    foreach ($projects as &$project) {
      $project->sort();
    }
    ksort($projects);

    return [
      $modules,
      $projects,
    ];
  }

  public function __toString() {
    return $this->name;
  }

  public function getProject() {
    if (!isset($this->info['project'])) {
      if (isset($this->info['package']) && $this->info['package'] == 'Core') {
        $project = 'drupal';
      }
      else {
        $project = 'unknown';
      }
    }
    else {
      $project = $this->info['project'];
    }

   return $project;
  }

  public function isHidden() {
    return !empty($this->info['hidden']);
  }

  public function isEnabled() {
    return !empty($this->status);
  }
}
