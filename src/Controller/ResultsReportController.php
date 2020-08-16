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
