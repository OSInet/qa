<?php

/**
 * @file
 * qa.variables.inc
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

use Drupal\qa\Plugin\Qa\Control\Variable\Variable;

function qa_report_variable(Variable $variable = NULL) {
  $bc = drupal_get_breadcrumb();
  $bc[] = l(t('Administration'), 'admin');
  $bc[] = l(t('Reports'), 'admin/reports');
  $bc[] = l(t('Quality Assurance'), 'admin/reports/qa');
  $bc[] = l(t('Variables'), 'admin/reports/qa/variable');
  drupal_set_breadcrumb($bc);

  drupal_set_title(t('Variable: %name', array('%name' => $variable->name)), PASS_THROUGH);
  return $variable->dump();
}

/**
 * Page callback for variables list.
 *
 * @return string
 */
function qa_report_variables() {
  $cached = cache_get('variables', 'cache_bootstrap');
  if ($cached === FALSE) {
    $cached = (object) array(
      'data' => array(),
      'serialized' => FALSE,
    );
  }

  $header = array(
    t('Name'),
    t('Length'),
    t('Value, or size for arrays'),
  );

  $rows = array();
  foreach ($cached->data as $name => $value) {
    $row = array();
    $variable = new Variable($name);
    $row[] = $variable->link();
    $row[] = is_scalar($value) ? strlen($value) : strlen(serialize($value));

    if (is_array($value)) {
      $cell = t("Array[@count]", array('@count' => count($value)));
    } else {
      $raw = is_string($value) ? $value : serialize($value);
      $raw = check_plain($raw);
      $cell = (strlen($raw) > 200) ? substr($raw, 0, 200) . '&hellip;' : $raw;
    }
    $row[] = $cell;
    $rows[] = $row;
  }

  usort($rows, function ($x, $y) {
    if ($x[1] < $y[1]) {
      $ret = -1;
    } elseif ($x[1] > $y[1]) {
      $ret = 1;
    } else {
      $ret = strcmp($x[0], $y[0]);
    }
    return - $ret;
  });
  return theme('table', array(
    'header' => $header,
    'rows' => $rows,
  ));
}

