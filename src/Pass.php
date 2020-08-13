<?php

declare(strict_types=1);

namespace Drupal\qa;

use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\user\Entity\User;

/**
 * Pass represents the results of a QaCheck run, aka a "Pass" in a check suite.
 */
class Pass {

  /**
   * The QaCheck instance of which this is a pass
   *
   * @var \Drupal\qa\Plugin\QaCheckInterface
   */
  public $check;

  /**
   * The user who ran the pass.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * The pass lifecycle
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
   * Serializable array for the results
   *
   * @param array
   */
  public $result;

  /**
   * @param \Drupal\qa\Plugin\QaCheckInterface $check
   */
  function __construct(QaCheckInterface $check) {
    $this->life = new Same();
    $this->ok = TRUE;
    $this->check = $check;
    $this->account = $GLOBALS['user'] ?? User::getAnonymousUser();
    $this->result = [];
  }

  /**
   * Record results from one of the checks in a control pass
   *
   * @param Result $checkResult
   *   - status: 0 or 1
   *   - result: render array
   *
   * @return void
   */
  function record(Result $checkResult) {
    if (!$checkResult->ok) {
      $this->ok = FALSE;
    }
    $this->result[$checkResult->name] = $checkResult;
    $this->life->modify();
  }

}
