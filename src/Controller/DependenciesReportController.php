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
   * @return string
   *   Return Hello string.
   */
  public function report() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: report'),
    ];
  }

  /**
   * Placeholder controller for "dependencies".
   *
   * @param string $qaVariable
   *   The variable name.
   *
   * @return array
   *   A render array.
   */
  public function view($qaVariable) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: view'),
    ];
  }

}
