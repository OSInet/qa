<?php

declare(strict_types = 1);

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ResultsReportController.
 */
class ResultsReportController extends ControllerBase {

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
   * Placeholder controller for "results".
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
