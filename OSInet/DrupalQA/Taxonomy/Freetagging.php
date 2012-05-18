<?php

namespace OSInet\DrupalQA\Taxonomy;

/**
 * Find views containing PHP code
 */
class Freetagging extends Taxonomy {

  public function __construct() {
    parent::__construct();
    $this->title = t('Unused freetagging terms');
    $this->description = t('Unused freetagging terms mean useless volume. Removing them helps makin term autocompletes more relevant.');
  }

  /**
   * List unused tags.
   *
   * @param object $vocabulary
   *
   * @return array
   */
  function checkTags($vocabulary) {
    $sq = <<<sql
SELECT td.tid
FROM {term_data} td
  LEFT JOIN {term_node} tn ON td.tid = tn.tid
WHERE
  td.vid = %d AND tn.nid IS NULL
sql;
    // no db_rewrite_sql(): we are checking the whole database
    $q = db_query($sq, $vocabulary->vid);
    $result = array(
      'vocabulary' => $vocabulary,
      'terms' => array(),
    );
    while ($o = db_fetch_object($q)) {
      $term = taxonomy_get_term($o->tid); // has an internal cache, so we may loop
      $result['terms'][$term->tid] = l($term->name, 'admin/content/taxonomy/edit/term/'. $term->tid, array(
        'query' => array('destination' => 'admin/reports/qa/result'),
      ));
    }
    $ret = array(
      'name'   => $vocabulary->name,
      'status' => empty($result['terms']) ? 1 : 0,
      'result' => $result,
    );
    return $ret;
  }

  function run() {
    $pass = parent::run();
    $vocabularies = taxonomy_get_vocabularies();
    foreach ($vocabularies as $vid => $vocabulary) {
      if ($vocabulary->tags) {
        $pass->record($this->checkTags($vocabulary));
      }
    }
    $pass->life->end();

    // Prepare for theming
    $result = '';
    uksort($pass->result, 'strcasecmp'); // @XXX May be inconsistent with non-BMP strings ?
    foreach ($pass->result as $vocabulary_name => $info) {
      $vocabulary_link = l($vocabulary_name, 'admin/content/taxonomy/'. $info['vocabulary']->vid);
      $result[] = t('!link: !terms', array(
        '!link' => $vocabulary_link,
        '!terms' => implode(', ', $info['terms']),
      ));
    }
    $result = empty($result)
      ? t('All tags are in use')
      : theme('item_list', $result);
    $pass->result = $result;
    return $pass;
  }
}
