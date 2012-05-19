<?php

namespace OSInet\DrupalQA\Views;

class Overrides extends Views {

  public function __construct() {
    parent::__construct();
    $this->title = t('Non-default views');
    $this->description = t('Have any views been overridden or only created in the DB ? This is a performance and change management issue.');
  }

  function checkViewType($view) {
    $status = $view->type == t('Default') ? 1 : 0;
    if (!$status) {
      $name = empty($view->human_name) ? $view->name : $view->human_name;
      $result = array(
          module_exists('views_ui')
          ? l($name, "admin/structure/views/view/{$view->name}/edit")
          : $name,
          $view->type);
    }
    else {
      $result = NULL;
    }
    $ret = array(
      'name'   => $view->name,
      'status' => $status,
      'result' => $result,
    );
    return $ret;
  }

  function run() {
    $pass = parent::run();
    $views = views_get_all_views(TRUE);
    foreach ($views as $view) {
      $pass->record($this->checkViewType($view));
    }
    $pass->life->end();

    // Prepare for theming
    $result = array();
    uksort($pass->result, 'strcasecmp'); // @XXX May be inconsistent with non-BMP strings ?
    foreach ($pass->result as $view_name => $view_report) {
      $result[] = t('!view: @type', array(
          '!view' => $view_report[0], // Build safe in self::checkViewPhp
          '@type' => $view_report[1],
      ));
    }
    $result = theme('item_list', array('items' => $result));
    $pass->result = $result;
    return $pass;
  }
}
