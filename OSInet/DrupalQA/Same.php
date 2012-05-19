<?php

namespace OSInet\DrupalQA;

/**
 * A lifetime class.
 *
 * Properties are: Start, Access, Modify, End
 *
 *
 * Note: SAME is an old (ca 2005) OSInet PHP_lib class, which other modules may
 * have imported in a non-namespaced form.
 */
class Same {
  /**
   * Start time.
   *
   * @var int
   */
  public $s;

  /**
   * Access time.
   *
   * @var int
   */
  public $a;

  /**
   * Modification time.
   *
   * @var int
   */
  public $m;

  /**
   * End time.
   *
   * @var int
   */
  public $e;

  /**
   * Constructor
   *
   * S.A.M. default to current time, but E defaults to NULL.
   *
   * @param int $s
   * @param int $a
   * @param int $m
   * @param int $e
   */
  function __construct($s = NULL, $a = NULL, $m = NULL, $e = NULL) {
    $now = time();
    foreach (array('s', 'a', 'm') as $ts) {
      $this->$ts = isset($$ts) ? $$ts : $now;
    }
  }

  /**
   * Update the access time.
   *
   * @param int $now
   */
  public function access($now = NULL) {
    $this->a = isset($now) ? $now : time();
  }

  /**
   * Update the modification time.
   *
   * @param int $now
   */
  public function modify($now = NULL) {
    if (!isset($now)) {
      $now = time();
    }
    $this->access($now);
    $this->m = $now;
  }

  /**
   * Update the end time.
   *
   * @param int $now
   */
  public function end($now = NULL) {
    if (!isset($now)) {
      $now = time();
    }
    $this->modify($now);
    $this->e = $now;
  }
}
