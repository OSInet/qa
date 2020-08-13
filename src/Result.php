<?php
declare(strict_types = 1);

namespace Drupal\qa;

/**
 * Result represents the results of a single step in a QaCheck.
 *
 * Multiple Result values can be present in a Pass, each keyed under its name.
 */
class Result {

  /**
   * Data can be anything serializable.
   *
   * @var mixed
   */
  public $data;

  /**
   * The name of the key under which to store the results in a QaCheck Pass.
   *
   * @var string
   */
  public $name;

  /**
   * Did the QaCheck pass?
   *
   * @var bool
   */
  public $ok;

  /**
   * Result constructor.
   *
   * @param string $name
   * @param bool $status
   * @param null $data
   */
  public function __construct(string $name, bool $status, $data = NULL) {
    $this->name = $name;
    $this->ok = $status;
    $this->data = $data;
  }
}
