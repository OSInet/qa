<?php

namespace OSInet\DrupalQA;

/**
 * A lifetime class
 *
 * Properties are: Start, Access, Modify, End
 *
 *
 * WARNING: SAME is an old (ca 2005) OSInet library class, which other modules
 * may have imported. Older versions should be removed.
 */
class Same {
  public $s;
  public $a;
  public $m;
  public $e;

  /**
   * Constructor
   *
   * S.A.M. default to current time, but E has no default.
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

  public function access($now = NULL) {
    $this->a = isset($now) ? $now : time();
  }

  public function modify($now = NULL) {
    if (!isset($now)) {
      $now = time();
    }
    $this->access($now);
    $this->m = $now;
  }

  public function end($now = NULL) {
    if (!isset($now)) {
      $now = time();
    }
    $this->modify($now);
    $this->e = $now;
  }
}
