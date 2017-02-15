<?php

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DependenciesReportController.
 *
 * @package Drupal\qa\Controller
 */
class DependenciesReportController extends ControllerBase {

  /**
   * Action.
   *
   * @return string
   *   Return Hello string.
   */
  public function report() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: report'),
    ];
  }

  public function view($qaVariable) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: view'),
    ];
  }

}
