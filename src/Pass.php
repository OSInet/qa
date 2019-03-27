<?php

namespace Drupal\qa;

use Drupal\qa\Plugin\Qa\Control\BaseControl;

/**
 * A control pass.
 */
class Pass {
  /**
   * The control of which this is a pass.
   *
   * @var \Drupal\qa\Plugin\Qa\Control\BaseControl
   */
  public $control;

  /**
   * The user who ran the pass.
   *
   * This is normally a stdClass with a $uid public member.
   *
   * @var object
   */
  public $account;

  /**
   * The pass lifecycle.
   *
   * @var \Drupal\qa\Same
   */
  public $life;

  /**
   * Success or failure.
   *
   * @var int
   *   - NULL: undefined
   *   - 0: failure
   *   - 1: success
   */
  public $status;

  /**
   * Dual-responsibility results accumulator.
   *
   * - While running checks, accumulates the results of each check
   * - When returning from <Foo>Control::run, a rendered string.
   *
   * @var array|string
   *
   * TODO convert to Single Responsibility
   */
  public $result;

  /**
   * Pass constructor.
   *
   * @param \Drupal\qa\Plugin\Qa\Control\BaseControl $control
   *   The control for which to run a Pass.
   */
  public function __construct(BaseControl $control) {
    $this->life = new Same();
    $this->status = NULL;
    $this->control = $control;
    $this->account = $GLOBALS['user'];
    $this->result = array();
  }

  /**
   * Record results from one of the checks in a control pass.
   *
   * @param array $check
   *   Contains at least those keys:
   *   - status: 0 or 1
   *   - result: render array.
   */
  public function record(array $check) {
    if (!$check['status']) {
      $this->status = 0;
      if (isset($check['name'])) {
        $this->result[$check['name']] = $check['result'];
      }
      else {
        $this->result[] = $check['result'];
      }
    }
    elseif (!isset($this->status)) {
      $this->status = 1;
    }
    $this->life->modify();
  }

}
