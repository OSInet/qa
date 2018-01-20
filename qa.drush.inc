<?php

/**
 * @file
 * Drush plugin.
 */

use Drupal\qa\Controller\WorkflowsReportController;
use Drupal\qa\Workflows\ContentModerationGraph;
use Drupal\qa\Workflows\ContentModerationGrid;
use Drupal\qa\Workflows\WorkflowList;
use Symfony\Component\Yaml\Yaml;

use OSInet\qa\ForceRemoved;

/**
 * Implementation of hook_drush_command().
 */
function qa_drush_command() {
  $items['como-table'] = [
    'aliases' => ['cmt'],
    'description' => 'Show the content moderation as a table.',
  ];

  $items['como-graphviz'] = [
    'aliases' => ['cmg'],
    'description' => 'Show the content moderation as a Graphviz DOT file.',
    'arguments' => [
      'workflow' => 'The machine name of a workflow',
    ]
  ];

  $items['qa-workflows-list'] = [
    'aliases' => ['qawl'],
    'description' => 'Show a summary of available workflows',
  ];

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

function drush_qa_como_graphviz() {
  $graph = ContentModerationGraph::create(\Drupal::getContainer());
  echo $graph->report();
}

/**
 * Command callback for qa-workflows-list.
 */
function drush_qa_workflows_list() {
  $listBuilder = WorkflowsReportController::create(Drupal::getContainer());
  $list = $listBuilder->getWorkflowSummary(
    $listBuilder->storage->loadMultiple()
  );
  drush_print(Yaml::dump($list));
}

function drush_qa_como_table() {
  $table = ContentModerationGrid::create(\Drupal::getContainer());
  $table->report();
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
