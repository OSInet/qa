<?php
// $Id$
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

/**
 * Find views containing PHP code
 */
class QaControlTaxonomyFreetagging extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
    $this->title = t('Unused freetagging terms');
    $this->description = t('Unused freetagging terms mean useless volume. Removing them helps makin term autocompletes more relevant.');
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('taxonomy'));
    return $ret;
  }

  function checkTags($vocabulary) {
    $sq = <<<EOT
SELECT td.tid
FROM {term_data} td
  LEFT JOIN {term_node} tn ON td.tid = tn.tid
WHERE
  td.vid = %d AND tn.nid IS NULL
EOT;
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
    return array(
      'name'   => $vocabulary->name,
      'status' => empty($result['terms']) ? 1 : 0,
      'result' => $result,
    );
  }

  function run() {
    $pass = parent::run();
    $vocabularies = taxonomy_get_vocabularies();
    foreach ($vocabularies as $vid => $vocabulary) {
    // dsm($vocabulary);
      if ($vocabulary->tags) {
        $pass->record($this->checkTags($vocabulary));
      }
    }
    $pass->life->end();

    // Prepare for theming
    $result = '';
    foreach ($pass->result as $vocabulary_name => $info) {
      $vocabulary_link = l($vocabulary_name, 'admin/content/taxonomy/'. $info['vocabulary']->vid);
      $result[] = t('!link: !terms', array(
        '!link' => $vocabulary_link,
        '!terms' => implode(', ', $info['terms']),
      ));
    }
    $result = theme('item_list', $result);
    $pass->result = $result;
    return $pass;
  }
}

class QaControlTaxonomyOrphans extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
    $this->title = t('Inconsistent node tagging');
    $this->description = t('Check for term_node entries pointing to a missing node or term. These should never happen, and should be removed when they do.');
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('taxonomy'));
    return $ret;
  }

  function run() {}
}
