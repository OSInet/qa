<?php
/**
 * @file
 * Variable.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Drupal\qa\Variable;


class Variable {
  public $is_set;
  public $name;
  public $value;
  public $default;

  public function __construct($name) {
    $this->name = $name;
    $this->is_set = isset($GLOBALS['conf'][$name]);
    if ($this->is_set) {
      $this->value = $GLOBALS['conf'][$name];
    }

    if (function_exists('variable_get_default')) {
      $this->default = variable_get_default($name);
    }
  }

  public function dump() {
    return kprint_r($this->value, TRUE, $this->name);
  }

  public function link() {
    return l($this->name, "admin/reports/qa/variable/{$this->name}");
  }
}
