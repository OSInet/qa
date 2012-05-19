<?php

namespace OSInet\DrupalQA\Views;

/**
 * Find views containing PHP code
 */
class Php extends Views {

  public function __construct() {
    parent::__construct();
    $this->title = t('PHP code within views');
    $this->description = t('Is there any embedded PHP within views and display definitions ? This is both a security risk and a performance issue.');
  }

  /**
   * Views 2 had a single string for areas whereas Views 3 has an array for them.
   */
  function checkViewPhp($view) {
    $php = $this->getPhpFormats();
    $result = array();
    foreach ($view->display as $display_name => $display) {
      foreach (array('header', 'footer', 'empty') as $area_name) {
        $area = isset($display->display_options[$area_name])
          ? $display->display_options[$area_name]
          : NULL;
        if (!isset($area)) {
          continue;
        }

        // Views 3 format.
        if (is_array($area)) {
          foreach ($area as $field => $field_options) {
            switch ($field_options['field']) {
              case 'area':
                if (in_array($field_options['format'], $php)) {
                  $result[$display_name][$area_name][$field] = $field_options['content'];
                }
                break;
            }
          }
        }
        // Views 2 format: should no longer exist on D7.
        else {
          $area_format = $display->display_options[$area_name .'_format']; // Always set
          if (in_array($area_format, $php)) {
            $result[$display_name][$area_name]['text'] = $area;
          }
        }
      } // foreach header, footer, empty...
    } // foreach display

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
      $result = format_plural(count($views), '1 view checked, none containing PHP',
        '@count views checked, none containing PHP', array());
    }
    else {
      $result = format_plural(count($views), '1 view checked and containing PHP',
        '@count views checked, @php containing PHP', array(
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
        $link_title = empty($views[$view_name]->human_name)
          ? $view_name
          : $views[$view_name]->human_name;
        $view_link = l($link_title, "admin/structure/views/view/$view_name/edit");
        $row['view'] = array('data' => $view_link);
        foreach ($displays as $display_id => $areas) {
          $row['display'] = l($display_id, "admin/structure/views/view/$view_name/edit/$display_id");
          foreach ($areas as $area_name => $fields) {
            $row['area'] = l($area_name, "admin/structure/views/nojs/rearrange/$view_name/$display_id/$area_name");
            foreach ($fields as $field => $content) {
              $row['field'] = l($field,
                'admin/structure/views/nojs/config-item/'. $view_name .'/'. $display_id .'/'. $area_name .'/'. $field,
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
      $result .= theme('table', array(
        'header' => $header,
        'rows' => $data,
      ));
    }
    $pass->result = $result;
    return $pass;
  }
}

