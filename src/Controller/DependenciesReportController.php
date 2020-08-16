<?php

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DependenciesReportController.
 */
class DependenciesReportController extends ControllerBase {

  /**
   * Action.
   *
   * @return array
   *   A render array.
   */
  public function report() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: report'),
    ];
  }

}
