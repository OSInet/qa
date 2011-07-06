<?php
/**
 * Implementation of hook_drush_command().
 */
function qa_dependencies_drush_command() {
//   $options['--alias'] = 'The alias defined in the variable mongodb_collections.';

  $items['qa-dependencies'] = array(
    'description' => 'Build a Graphviz DOT file showing the module and theme dependencies on the site',
    'aliases' => array('qadep'),
  );

  return $items;
}

function drush_qa_dependencies() {
  module_load_include('inc', 'qa', 'qa_dependencies');
  $G = qa_dependencies();
  echo $G->parse();
}
