<?php

/**
 * @file
 * Drush plugin.
 */

use OSInet\DrupalQA\ForceRemoved;

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
  return $items;
}

function drush_qa_dependencies() {
  module_load_include('inc', 'qa', 'qa_dependencies');
  $G = qa_dependencies();
  echo $G->parse();
}

function drush_qa_force_removed() {
  $finder = ForceRemoved::create();
  echo $finder->find();
}