<?php

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ProjectsReportController.
 *
 * @package Drupal\qa\Controller
 */
class ProjectsReportController extends ControllerBase {

  /**
   * Action.
   *
   * @return string
   *   Return Hello string.
   */
  public function action() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: action')
    ];
  }

}
