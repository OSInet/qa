<?php
function qa_dependencies() {
  $modules = module_rebuild_cache();
  krsort($modules);

  $themes = list_themes();
  krsort($themes);

  require_once(_graphviz_create_filepath(variable_get('graphviz_filter_pear_path', ''), 'Image/GraphViz.php'));

  $font = array('fontsize' => 10);
  $G = new Image_Graphviz(TRUE);
  $G->addAttributes(array('rankdir' => 'RL') + $font);

  $packages = array();

  $theme_shape = 'octagon';

  $engines = array();
  $engine_shape = 'doubleoctagon';

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

  foreach ($themes as $theme => $detail) {
//    if (!$detail->status) {
//      continue;
//    }
    if (!empty($detail->owner)) {
      $engine = basename($detail->owner); // with extension

      // plain PHP theme
      if (basename($engine, '.theme') == $theme) {
        $engine_base = basename($engine, '.theme');
        $shape = array('shape' => $engine_shape);
        $G->addNode($theme, $font + $shape, $engine_base);
      }
      else {
        $engine_base = basename($engine, '.engine');
        $shape = array('shape' => $theme_shape);
        $G->addEdge(array($theme => $engine_base), $font + array('style' => 'dotted'));
        $G->addNode($theme, $font + $shape, urlencode($engine_base));
      }

      $engines[$engine_base] = $engine_base;
    }
    else {
      $G->addNode($theme, $font + $shape);
    }

    if (!empty($detail->base_theme)) {
      $G->addEdge(array($theme => $detail->base_theme), $font + array('style' => 'dashed'));
    }
    //dsm($info, $module);
  }

  ksort($engines);
  foreach ($engines as $engine) {
    $G->addNode($engine, $font + array('shape' => $engine_shape));
    $G->addCluster(urlencode($engine), $engine, $font);
  }

  ksort($packages);
  foreach ($packages as $package) {
    $G->addCluster(urlencode($package), $package, $font);
  }
  return $G;
}
