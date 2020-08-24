<?php
/**
 * @file
 * Project.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2018 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Drupal\qa\Plugin\Qa\Control\System;

/**
 * Class ProjectsScanner scans projects for actual usage.
 *
 * @package Drupal\qa\Plugin\Qa\Control\System
 */
class ProjectsScanner {

  protected $projects = [];

  /**
   * Legacy method to load projects.
   *
   * Mostly used to double-check normal results.
   *
   * @see \Drupal\qa\Plugin\Qa\Control\System\ProjectsScanner::loadList()
   *
   * @internal
   */
  protected function loadListLegacy() {
    global $conf;
    drupal_static_reset('update_get_projects');
    $savedUpdateCheck = $conf['update_check_disabled'];
    $conf['update_check_disabled'] = TRUE;
    $projects = update_get_projects();
    $conf['update_check_disabled'] = $savedUpdateCheck;
    return $projects;
  }

  public static function loadList() {
    list(, $projects) =  Module::getInfo();
    return $projects;
  }

  public static function create() {
  }

  /**
   * @param bool $onlyUnused
   *
   * @return array
   */
  public function scan($onlyUnused = FALSE): array {
    if (empty($this->projects)) {
      $this->projects = static::loadList();
    }

    $result = [];

    /**
     * @var string $name
     * @var \Drupal\qa\Plugin\Qa\Control\System\Project $project
     */
    foreach ($this->projects as $name => $project) {
      $count = $project->useCount();
      if ($onlyUnused && $count) {
        continue;
      }

      $components = array_reduce($project->modules, function (array $accu, Module $module) {
        $accu[$module->name] = $module->isEnabled();
        return $accu;
      }, []);
      $result[$name] = ['components' => $components];

      // If $onlyUsed, all counts are 0, so useless.
      if (!$onlyUnused) {
        $result[$name]['usage'] = $count;
        }
    }

    return $result;
  }
}
