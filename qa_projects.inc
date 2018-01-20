<?php
/**
 * @file
 * qa.projects.inc
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014-2018 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

use OSInet\DrupalQA\System\Module;

/**
 * Apparently unused, and incorrect, function.
 *
 * FIXME found out whether it serves any purpose in the general case.
 *  It was likely added there as a one-time answer during a specific audit.
 *
 * @param \Variable|NULL $variable
 * @return string
 */
function qa_report_project(Variable $variable = NULL) {
  $c = cache_get('views_data:fr', 'cache_views');
  $ret = '<pre>' . json_encode($c, JSON_PRETTY_PRINT) . '</pre>';
  return $ret;
  $bc = drupal_get_breadcrumb();
  $bc[] = l(t('Administration'), 'admin');
  $bc[] = l(t('Reports'), 'admin/reports');
  $bc[] = l(t('Quality Assurance'), 'admin/reports/qa');
  $bc[] = l(t('Variables'), 'admin/reports/qa/variable');
  drupal_set_breadcrumb($bc);

  drupal_set_title(t('Variable: %name', array('%name' => $variable->name)), PASS_THROUGH);
  return $variable->dump();
}

/**
 * Page callback for projects list.
 *
 * @return string
 *
 * TODO convert to use ProjectsScanner
 * TODO find why an \Exception may be thrown.
 *
 * @see \OSInet\DrupalQA\System\ProjectsScanner
 *
 * @throws \Exception
 *   Reported by PhpStorm, but unidentified origin.
 */
function qa_report_projects() {
  list(, $projects) = Module::getInfo();

  $header = array(
    t('Project'),
    t('Module'),
    t('Module status'),
    t('Project status'),
  );

  $rows = array();
  $previous_project = '';
  /** @var \OSInet\DrupalQA\System\Project $project */
  foreach ($projects as $name => $project) {
    $row = array();
    $project_cell = array(
      'data' => $name,
    );
    $count = $project->useCount();
    if ($count > 1) {
      //$project_cell['rowspan'] = $count;
    }
    if ($name != $previous_project) {
      $previous_project = $name;
    }

    $enabled = t('Enabled');
    $disabled = t('Disabled');

    /** @var \OSInet\DrupalQA\System\Module $module */
    foreach (array_values($project->modules) as $index => $module) {
      $row = array();
      $row[] = ($index === 0) ? $project_cell : '';

      $row[] = $module->name;
      $row[] = $module->isEnabled() ? $enabled : $disabled;

      if ($index === 0) {
        if ($count === 0) {
          $last_cell = array(
            'style' => 'background-color: #ff8080',
            'data' => $count,
          );
        }
        else {
          $last_cell = $count;
        }
      }
      else {
        $last_cell = '';
      }
      $row[] = $last_cell;

      $rows[] = $row;
    }
  }
  return theme('table', array(
    'header' => $header,
    'rows' => $rows,
  ));
}
