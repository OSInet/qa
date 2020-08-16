<?php

declare(strict_types = 1);

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class VariablesReportController.
 */
class VariablesReportController extends ControllerBase {

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

  /**
   * Placeholder controller for "variables".
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
