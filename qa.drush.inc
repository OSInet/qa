<?php

/**
 * @file
 * Drush plugin.
 */

use OSInet\DrupalQA\ForceRemoved;
use OSInet\DrupalQA\System\ProjectsScanner;

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
