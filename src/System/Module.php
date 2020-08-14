<?php

declare(strict_types = 1);

namespace Drupal\qa\System;

use Drupal\Core\Extension\Extension;

/**
 * Class Module represents information about a module.
 */
class Module {

  /**
   * The name of the module file.
   *
   * @var string
   */
  public $filename;

  /**
   * Is the module hidden ?
   *
   * @var bool
   */
  public $hidden = FALSE;

  /**
   * The name of the module.
   *
   * @var string
   */
  public $name;

  /**
   * Misc information about the module.
   *
   * @var array
   */
  public $info;

  /**
   * The module extension type: "module" or "profile".
   *
   * @var string
   */
  public $type;

  /**
   * Is the module enabled (1 for true) ?
   *
   * @var int
   */
  public $status;

  /**
   * The current database schema version for the module.
   *
   * @var string
   */
  // phpcs:ignore
  public $schema_version;

  /**
   * The module weight, used in hooks.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The extensions depending on this module.
   *
   * @var array
   */
  // phpcs:ignore
  public $required_by = [];

  /**
   * The extensions this module depends on.
   *
   * @var array
   */
  public $requires = [];

  /**
   * The module sort order, not to be confused with weight.
   *
   * @var int
   */
  public $sort;

  /**
   * Create a Module description from a core Extension object.
   *
   * @param \Drupal\Core\Extension\Extension $object
   *   The module information, as provided by core.
   *
   * @return \Drupal\qa\System\Module
   *   The module information, ready to use.
   */
  public static function createFromCore(Extension $object): Module {
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
   * Extract information from the modules and their projects.
   *
   * FIXME very incomplete: relies on the D7 info, missing most D8/9 properties.
   *
   * @return array
   *   A list of modules and theme descriptions.
   */
  public static function getInfo() {
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

  /**
   * Builtin.
   *
   * @return string
   *   The name of the module.
   */
  public function __toString() {
    return $this->name;
  }

  /**
   * Obtain a valid project name for any module.
   *
   * @return string
   *   The name of the project associated to that module.
   */
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

  /**
   * Is the module hidden ?
   *
   * @return bool
   *   Is it ?
   */
  public function isHidden() {
    return !empty($this->info['hidden']);
  }

  /**
   * Is the module installed ? (enabled is D7 wording).
   *
   * @return bool
   *   Is it ?
   */
  public function isEnabled() {
    return !empty($this->status);
  }

}
