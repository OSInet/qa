<?php
use OSInet\DrupalQA\System\Module;
use OSInet\DrupalQA\System\Project;

/**
 * @file
 * qa.projects.inc
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
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
 */
function qa_report_projects() {
  $ret = '<h3>Projects</h3>';
  drupal_static_reset('update_get_projects');
  $GLOBALS['conf']['update_check_disabled'] = TRUE;
  $projects = update_get_projects();
  ksort($projects);
  $ret .= kprint_r($projects, TRUE);

  $ret .= '<h3>Modules info</h3>';
  list($modules, $projects) = Module::getInfo();

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
