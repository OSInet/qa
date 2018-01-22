<?php

/**
 * @file
 * Drush plugin.
 */

use Drupal\qa\Plugin\Qa\Control\ForceRemoved;
use Drupal\qa\Plugin\Qa\Control\System\ProjectsScanner;

/**
 * Implementation of hook_drush_command().
 */
function qa_drush_command() {
  $items['qa-dependencies'] = [
    'description' => 'Build a Graphviz DOT file showing the module and theme dependencies on the site',
    'aliases' => ['qadep'],
  ];

  $items['qa-force-removed'] = [
    'description' => 'List extensions removed without a clean uninstall.',
    'aliases' => ['qafrm'],
  ];

  $items['qa-project-usage'] = [
    'description' => 'List usage and components for projects',
    'options' => [
      'only-unused' => 'Only include totally unused packages',
    ],
    'aliases' => ['qapus'],
  ];

  $items['qa-variables'] = [
    'description' => 'List size of variables',
    'aliases' => ['qavar'],
  ];

  return $items;
}

/**
 * Command callback for qa-dependencies.
 */
function drush_qa_dependencies() {
  module_load_include('inc', 'qa', 'qa_dependencies');
  $G = qa_dependencies();
  echo $G->parse();
}

/**
 * Command callback for qa-force-removed.
 */
function drush_qa_force_removed() {
  $finder = ForceRemoved::create();
  echo $finder->find();
}

/**
 * Command callback for qa-project-usage.
 */
function drush_qa_project_usage() {
  $onlyUnused = drush_get_option('only-unused', FALSE);
  $scanner = new ProjectsScanner();
  $result = $scanner->scan($onlyUnused);
  echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}

/**
 * Callback for uasort() in drush_qa_variables().
 *
 * @param $x
 * @param $y
 * @return int
 *
 * @see \drush_qa_variables()
 */
function _qa_drush_compare_variables($x, $y) {
  return - ($x['len'] <=> $y['len']);
}

/**
 * Command callback for qa-variables.
 */
function drush_qa_variables() {
  $result = [
    'total' => 0,
    'detail' => [],
  ];

  $cached = cache_get('variables', 'cache_bootstrap');
  foreach ($cached->data as $name => $value) {
    $len = is_scalar($value) ? strlen($value) : strlen(serialize($value));
    $result['total'] += $len;
    if (is_array($value)) {
      $preview = t("Array[@count]", array('@count' => count($value)));
    }
    else {
      $raw = is_string($value) ? $value : serialize($value);
      $raw = check_plain($raw);
      $preview = (strlen($raw) > 80) ? substr($raw, 0, 80) . '...' : $raw;
    }
    $result['detail'][$name] = ['len' => $len, 'preview' => $preview];
  }
  uasort($result['detail'], _qa_drush_compare_variables::class);

  echo json_encode($result, JSON_PRETTY_PRINT);
}