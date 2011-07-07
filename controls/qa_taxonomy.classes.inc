<?php
/**
 * @file
 * OSInet QA Plugin for Taxonomy 6.x
 *
 * @copyright Copyright (C) 2006-2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-4.7
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

class QaPackageTaxonomy extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('Taxonomy quality controls');
    $this->description = t('Look for orphan freetags, and inconsistent node tagging');
  }
}

abstract class QaControlTaxonomy extends QaControl {

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('taxonomy'));
    return $ret;
  }
}

/**
 * Find views containing PHP code
 */
class QaControlTaxonomyFreetagging extends QaControlTaxonomy {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
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

class QaControlTaxonomyOrphans extends QaControlTaxonomy {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
    $this->title = t('Inconsistent node tagging');
    $this->description = t('Check for term_node entries pointing to a missing node or term. These should never happen, and should be removed when they do.');
  }

  /**
   * Locate {term_node} entries linking to a non-existent term or node revision.
   *
   * @return array
   */
  function checkOrphans() {
    $sq = <<<sql
SELECT tn.tid AS tntid,
  td.tid AS tdtid, td.name AS tdname,
  v.nid, v.vid
FROM {term_node} tn
  LEFT JOIN {term_data} td ON tn.tid = td.tid
  LEFT JOIN {node_revisions} v ON tn.nid = v.nid AND tn.vid = v.vid
WHERE td.tid IS NULL
  OR v.nid IS NULL or v.vid IS NULL
sql;
    // No db_rewrite_sql(): we are scanning the whole database for user 1
    $q = db_query($sq);
    $orphans = array(
      'terms' => array(),
      'revisions' => array(),
    );
    while ($o = db_fetch_object($q)) {
      if (is_null($o->tdtid)) {
        $orphans['terms'][] = $o->tntid;
      }
      if (is_null($o->nid) || is_null($o->vid)) {
        $orphans['revisions'][] = $o->tntid;
      }
    }
    $orphans['terms']     = array_unique($orphans['terms']);
    sort($orphans['terms']);
    $orphans['revisions'] = array_unique($orphans['revisions']);
    sort($orphans['revisions']);
    return array(
      'status' => (empty($orphans['terms']) && empty($orphans['revisions'])) ? 1 : 0,
      'result' => $orphans,
    );
  }

  function run() {
    $pass = parent::run();
    $pass->record($this->checkOrphans());
    $pass->life->end();

    // Prepare for theming
    $result = isset($pass->result[0]) ? $pass->result[0] : NULL; // only one pass for this check
    $result = array(
      empty($result['terms'])
        ? t('All terms found')
        : t('Missing term IDs: @tids', array('@tids' => implode(', ', $result['terms']))),
      empty($result['revisions'])
        ? t('All revisions found')
        : t('Missing node revisions: @vids', array('@vids' => implode(', ', $result['revisions']))),
    );
    $result = theme('item_list', $result);
    $pass->result = $result;
    return $pass;
  }
}
