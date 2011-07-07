<?php
function qa_dependencies() {
  $modules = module_rebuild_cache();
  krsort($modules);
  require_once(_graphviz_create_filepath(variable_get('graphviz_filter_pear_path', ''), 'Image/GraphViz.php'));

  $font = array('fontsize' => 10);
  $G = new Image_Graphviz(TRUE);
  $G->addAttributes(array('rankdir' => 'RL') + $font);
  $packages = array();
  foreach ($modules as $module => $detail) {
    if (!$detail->status) {
      continue;
    }
    if (!empty($detail->info['package'])) {
      $packages[$detail->info['package']] = $detail->info['package'];
      $G->addNode($module, $font, urlencode($detail->info['package']));
    }
    else {
      $G->addNode($module, $font);
    }

    foreach ($detail->info['dependencies'] as $depend) {
      $G->addEdge(array($module => $depend), $font);
    }
    //dsm($info, $module);
  }
  ksort($packages);
  foreach ($packages as $package) {
    $G->addCluster(urlencode($package), $package, $font);
  }
  return $G;
}