<?php
// $Id$
/**
 * @file
 * OSInet QA Plugin for Views 6.3
 *
 * @copyright Copyright (C) 2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-6
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 *
 * License note: QA is distributed by OSInet to its customers under the
 * CeCILL 2.0 license. OSInet support services only apply to the module
 * when distributed by OSInet, not by any third-party further down the
 * distribution chain.
 *
 * If you obtained QA from drupal.org, that site received it under the
 * GPLv2 license and can therefore distribute it under the GPLv2, and
 * so can you and just anyone down the chain as long as the GPLv2 terms
 * are abided by, the module distributor in that case being the
 * drupal.org organization or the downstream distributor, not OSInet.
 */

class QaPackageViews extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('Views quality controls');
    $this->description = t('Look for overridden views and views containing embedded PHP');
  }
}

/**
 * Shared abstract ancestor class for Views controls, to hold common dependencies.
 */
abstract class QaControlViews extends QaControl {
  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('views'));
    return $ret;
  }
}

/**
 * Find views containing PHP code
 */
class QaControlViewsPhp extends QaControlViews {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageViews';
    $this->title = t('PHP code within views');
    $this->description = t('Is there any embedded PHP within views and display definitions ? This is both a security risk and a performance issue.');
    parent::__construct();
  }

  function checkViewPhp($view) {
    $php = $this->getPhpFormats();
    $result = array();
    foreach ($view->display as $display_name => $display) {
      // dsm($display, $display_name);
      foreach (array('header', 'footer', 'empty') as $area_name) {
        if (isset($display->display_options[$area_name])) {
          foreach ($display->display_options[$area_name] as $field => $handler_options) {
            switch ($field) {
              case 'area':
                if (in_array($handler_options['format'], $php)) {
                  $result[$display_name][$area_name][$field] = $handler_options['content'];
                }
                break;
            }
          }
        }
      }
    }
    $ret = array('name' => $view->name, 'status' => empty($result), 'result' => $result);
    return $ret;
  }

  /**
   * Get a list of the ids of input formats containing the PHP eval filter.
   *
   * @return array
   */
  protected function getPhpFormats($reset = FALSE) {
    static $php = NULL;

    if (!isset($php) || $reset) {
      $formats = filter_formats();
      $php = array();
      foreach ($formats as $format) {
        $filters = filter_list_format($format->format);
        foreach ($filters as $filter) {
          if ($filter->module == 'php') {
            $php[] = $format->format;
            break;
          }
        }
      }
      // dsm($php);
    }
    return $php;
  }

  function run() {
    $pass = parent::run();
    $views = views_get_all_views(TRUE);
    foreach ($views as $view) {
      $pass->record($this->checkViewPhp($view));
    }
    $pass->life->end();

    if ($pass->status) {
      $result = format_plural(count($views), '1 view checked, none containing PHP', '@count views checked, none containing PHP', NULL);
    }
    else {
      $result = format_plural(count($views), '1 view checked and containing PHP', '@count views checked, @php containing PHP', array(
        '@php' => count($pass->result),
      ));
      $header = array(
        t('View'),
        t('Display'),
        t('Area'),
        t('Field'),
        t('Content'),
      );
      $data = array();
      foreach ($pass->result as $view_name => $displays) {
        $row = array();
        $view_link = empty($views[$view_name]->human_name)
          ? l($view_name,         'admin/build/views/edit/'. $view_name)
          : l($views[$view_name], 'admin/build/views/edit/'. $view_name);
        $row['view'] = array('data' => $view_link);
        foreach ($displays as $display_id => $areas) {
          $row['display'] = $display_id;
          foreach ($areas as $area_name => $fields) {
            $row['area'] = $area_name;
            foreach ($fields as $field => $content) {
              $row['field'] = l($field,
                'admin/build/views/nojs/config-item/'. $view_name .'/'. $display_id .'/'. $area_name .'/'. $field,
                array('query' => array('destination' => 'admin/reports/qa/results'))
              );
              $row['content'] = array(
                'data'  => '<pre>'. check_plain($content) .'</pre>',
                'class' => 'pre',
              );
              $data[$view_name .'/'. $display_id .'/'. $area_name .'/'. $field] = $row;
            }
          }
        }
      }
      ksort($data);
      // theme_table();
      $result .= theme('table', $header, $data);
    }
    $pass->result = $result;
    return $pass;
  }
}

class QaControlViewsOverrides extends QaControlViews {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageViews';
    $this->title = t('Non-default views');
    $this->description = t('Have any views been overridden or only created in the DB ? This is a performance and change management issue.');
    parent::__construct();
  }

  function checkViewType($view) {
    $status = $view->type == t('Default') ? 1 : 0;
    if (!$status) {
      $name = empty($view->human_name) ? $view->name : $view->human_name;
      $result = array(
      module_exists('views_ui')
          ? l($name, 'admin/build/views/edit/'. $view->name)
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
    $result = theme('item_list', $result);
    $pass->result = $result;
    return $pass;
  }
}
