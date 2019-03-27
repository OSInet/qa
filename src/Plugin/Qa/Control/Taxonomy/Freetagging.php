<?php

namespace Drupal\qa\Plugin\Qa\Control\Taxonomy;

use Drupal\qa\Pass;

/**
 * Find views containing PHP code
 */
class Freetagging extends Taxonomy {

  /**
   * {@inheritdoc]
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('Unused freetagging terms');
    $this->description = t('Unused freetagging terms mean useless volume. Removing them helps making term autocompletion more relevant.');
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
FROM {taxonomy_term_data} td
  LEFT JOIN {taxonomy_index} tn ON td.tid = tn.tid
WHERE
  td.vid = :vid AND tn.nid IS NULL
sql;
    // no db_rewrite_sql(): we are checking the whole database
    $q = db_query($sq, array(':vid' => $vocabulary->vid));
    $result = array(
      'vocabulary' => $vocabulary,
      'terms' => array(),
    );

    foreach ($q->fetchAll() as $o) {
      $term = taxonomy_term_load($o->tid); // has an internal cache, so we may loop
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

  function run(): Pass {
    $pass = parent::run();
    $vocabularies = taxonomy_get_vocabularies();
    foreach ($vocabularies as $vocabulary) {
      if (!empty($vocabulary->tags)) {
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
