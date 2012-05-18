<?php

namespace OSInet\DrupalQA\Taxonomy;

class Orphans extends Taxonomy {

  public function __construct() {
    parent::__construct();
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
