<?php

/**
 * This file was previously qa_projects.inc.
 */

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\qa\System\Module;
use Drupal\update\UpdateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProjectsReportController.
 *
 * @package Drupal\qa\Controller
 */
class ProjectsReportController extends ControllerBase {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  public function __construct(
    UpdateManagerInterface $updateManager,
    KeyValueExpirableFactoryInterface $kveFactory
  ) {
    $this->keyValueExpirable = $kveFactory->get('update');
    $this->updateManager = $updateManager;
  }

  public static function create(ContainerInterface $container) {
    $updateManager = $container->get('update.manager');
    $kve = $container->get('keyvalue.expirable');
    return new static($updateManager, $kve);
  }

  /**
   * Action.
   *
   * @return array<string,array|string>
   *   A render array
   */
  public function action() {
    $projects = $this->buildProjects();
    return $projects;
  }

  /**
   * Build the list of projects.
   *
   * On most sites, this will just hold "drupal" and "OSInet QA", since most
   * code does not use the undocumented project info file key.
   *
   * @return array
   *   A render array.
   */
  protected function buildProjects() {
    $this->keyValueExpirable->delete('update_project_projects');
    $projects = $this->updateManager->getProjects();
    $build = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Status'),
      ],
      '#rows' => [],
    ];

    foreach ($projects as $projectName => $project) {
      $row = [
        $projectName,
        $project['project_type'],
        $project['project_status'],
      ];
      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * @param \Drupal\qa\Controller\Variable|NULL $variable
   *
   * @return string
   *
   * FIXME this is is legacy D7 one-off code.
   *
   * @deprecated
   */
  private final function qa_report_project(Variable $variable = NULL) {
    $c = cache_get('views_data:fr', 'cache_views');
    $ret = '<pre>' . json_encode($c, JSON_PRETTY_PRINT) . '</pre>';
    return $ret;
    $bc = drupal_get_breadcrumb();
    $bc[] = l($this->t('Administration'), 'admin');
    $bc[] = l($this->t('Reports'), 'admin/reports');
    $bc[] = l($this->t('Quality Assurance'), 'admin/reports/qa');
    $bc[] = l($this->t('Variables'), 'admin/reports/qa/variable');
    drupal_set_breadcrumb($bc);

    drupal_set_title($this->t('Variable: %name', array('%name' => $variable->name)), PASS_THROUGH);
    return $variable->dump();
  }

  /**
   * Page callback for projects list.
   *
   * @return string
   *
   * @FIXME This still uses the D7 info structure.
   *
   * @deprecated
   */
  private final function qa_report_projects() {
    $ret = '<h3>Projects</h3>';
    drupal_static_reset('update_get_projects');
    $GLOBALS['conf']['update_check_disabled'] = TRUE;
    $projects = update_get_projects();
    ksort($projects);
    $ret .= kprint_r($projects, TRUE);

    $ret .= '<h3>Modules info</h3>';
    list($modules, $projects) = Module::getInfo();

    $header = array(
      $this->t('Project'),
      $this->t('Module'),
      $this->t('Module status'),
      $this->t('Project status'),
    );

    $rows = array();
    $previous_project = '';
    /** @var \Drupal\qa\System\Project $project */
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

      $enabled = $this->t('Enabled');
      $disabled = $this->t('Disabled');

      /** @var \Drupal\qa\System\Module $module */
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
}
