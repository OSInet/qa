<?php
function _qa_project_get_info($project) {
  return $project->info/*['dependencies']*/;
}

function qa_dependencies() {
  $modules = module_rebuild_cache();
  $modules = array_map('_qa_project_get_info', $modules);
  krsort($modules);
  require_once(_graphviz_create_filepath(variable_get('graphviz_filter_pear_path', ''), 'Image/GraphViz.php'));
  $G = new Image_Graphviz(TRUE);
  $G->addAttributes(array('rankdir' => 'RL'));
  $packages = array();
  foreach ($modules as $module => $info) {
    if (!empty($info['package'])) {
      $packages[$info['package']] = $info['package'];
      $G->addNode($module, array(), urlencode($info['package']));
    }
    else {
      $G->addNode($module);
    }

    foreach ($info['dependencies'] as $depend) {
      $G->addEdge(array($module => $depend));
    }
    //dsm($info, $module);
  }
  ksort($packages);
  foreach ($packages as $package) {
    $G->addCluster(urlencode($package), $package);
  }
  return $G;
}