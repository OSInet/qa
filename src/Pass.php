<?php

declare(strict_types = 1);

namespace Drupal\qa;

use Drupal\qa\Plugin\QaCheckInterface;

/**
 * Pass represents the results of a QaCheck run, aka a "Pass" in a check suite.
 */
class Pass {

  /**
   * The QaCheck instance of which this is a pass.
   *
   * @var \Drupal\qa\Plugin\QaCheckInterface
   */
  public $check;

  /**
   * The pass lifecycle.
   *
   * @var \Drupal\qa\Same
   */
  public $life;

  /**
   * Did all steps succeed ?
   *
   * @var bool
   */
  public $ok;

  /**
   * Serializable array for the results.
   *
   * @var array
   */
  public $result;

  /**
   * Pass constructor.
   *
   * @param \Drupal\qa\Plugin\QaCheckInterface $check
   *   A check on which to report.
   */
  public function __construct(QaCheckInterface $check) {
    $this->life = new Same();
    $this->ok = TRUE;
    $this->check = $check;
    $this->result = [];
  }

  /**
   * Record results from one of the checks in a control pass.
   *
   * @param Result|null $checkResult
   *   A check result to store.
   */
  public function record(?Result $checkResult) {
    if (empty($checkResult)) {
      return;
    }
    if (!$checkResult->ok) {
      $this->ok = FALSE;
    }
    $this->result[$checkResult->name] = $checkResult;
    $this->life->modify();
  }

}
