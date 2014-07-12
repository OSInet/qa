<?php

namespace OSInet\DrupalQA\Cache;

use OSInet\DrupalQA\BaseControl;

class Size extends BaseControl {
  const DATA_SIZE_LIMIT = 524288; // Memcache default entry limit: 1024*1024 * 0.5 for safety
  const DATA_SUMMARY_LENGTH = 1024;

  /**
   * {@inheritdoc]
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('Suspicious cache content');
    $this->description = t('Look for empty or extra-long (>= 1 MB) cache content.');
  }

  /**
   * Does the passed schema match the expected cache schema structure ?
   *
   * @param array $schema
   *
   * @return bool
   */
  public static function isSchemaCache(array $schema) {
    $reference_schema_keys = array(
      'cid',
      'created',
      'data',
      'expire',
      'serialized'
    );
    $keys = array_keys($schema['fields']);
    sort($keys);
    $ret = $keys == $reference_schema_keys;

    return $ret;
  }

  public static function getAllBins($rebuild = FALSE) {
    $schema = drupal_get_complete_schema($rebuild);
    $ret = array();
    foreach ($schema as $name => $info) {
      if (static::isSchemaCache($info)) {
        $ret[] = $name;
      }
    }
    sort($ret);

    return $ret;
  }

  /**
   * @param string $bin_name
   *
   * @return array
   *   - name: the name of the checked bin
   *   - status: 0 for KO, 1 for OK
   *   - result: information in case of failed check.
   */
  function checkBin($bin_name) {
    $ret = array(
      'name' => $bin_name,
    );
    $ret['status'] = FALSE;
    $arg = array('@name' => $bin_name);
    if (!db_table_exists($bin_name)) {
      $ret['result'] = t('Bin @name is missing in the database.', $arg);
      return $ret;
    }

    $sql = "SELECT cid, data, expire, created, serialized FROM {$bin_name} ORDER BY cid";
    $q = db_query($sql);
    if (!$q) {
      $ret['result'] = t('Failed fetching database data for bin @name.', $arg);
      return $ret;
    }

    $status = TRUE;
    $result = array();
    foreach ($q->fetchAll() as $row) {
      // Cache drivers will need to serialize anyway.
      $data = $row->serialized ? $row->data : serialize($row->data);
      $len = strlen($data);
      if ($len == 0 || $len >= static::DATA_SIZE_LIMIT) {
        $status = FALSE;
        $result[] = array(
          $row->cid,
          number_format($len, 0, ',', '&nbsp;'),
          check_plain(drupal_substr($data, 0, static::DATA_SUMMARY_LENGTH)) . '&hellip;',
        );
      }
    }

    $ret['status'] = $status;
    $ret['result'] = $result;

    return $ret;
  }

  function run() {
    $pass = parent::run();
    $bins = self::getAllBins(TRUE);
    foreach ($bins as $bin_name) {
      $pass->record($this->checkBin($bin_name));
    }
    $pass->life->end();

    if ($pass->status) {
      $pass->result = format_plural(count($bins), '1 bin checked, not containing suspicious values',
        '@count bins checked, none containing suspicious values', array());
    }
    else {
      $info = format_plural(count($bins), '1 view checked and containing suspicious values',
        '@count bins checked, @bins containing suspicious values', array(
          '@bins' => count($pass->result),
        ));

      // Prepare for theming
      $result = array();
      // @XXX May be inconsistent with non-BMP strings ?
      uksort($pass->result, 'strcasecmp');
      foreach ($pass->result as $bin_name => $bin_report) {
        foreach ($bin_report as $entry) {
          array_unshift($entry, $bin_name);
          $result[] = $entry;
        }
      }
      $header = array(
        t('Bin'),
        t('CID'),
        t('Length'),
        t('Beginning of data'),
      );

      $build = array(
        'info' => array(
          '#markup' => $info,
        ),
        'list' => array(
          '#markup' => '<p>' . t('Checked: @checked', array('@checked' => implode(', ', $bins))) . "</p>\n",
        ),
        'table' => array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $result,
        )
      );
      $pass->result = drupal_render($build);
    }
    return $pass;
  }

}
