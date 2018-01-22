<?php

namespace Drupal\qa;

/**
 * A control pass.
 */
class Pass {
  /**
   * The control of which this is a pass
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
   * The pass lifecycle
   *
   * @var \Drupal\qa\Same
   */
  public $life;

  /**
   * Success or failure
   *
   * @var int
   *   - NULL: undefined
   *   - 0: failure
   *   - 1: success
   */
  public $status;

  /**
   * Render array for the results
   *
   * @param array
   */
  public $result;

  /**
   * @param \Drupal\qa\Plugin\Qa\Control\BaseControl $control
   */
  function __construct($control) {
    $this->life = new Same();
    $this->status = NULL;
    $this->control = $control;
    $this->account = $GLOBALS['user'];
    $this->result = array();
  }

  /**
   * Record results from one of the checks in a control pass
   *
   * @param array $check
   *   - status: 0 or 1
   *   - result: render array
   *
   * @return void
   */
  function record($check) {
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
