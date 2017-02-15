<?php

namespace Drupal\qa\Taxonomy;

class Orphans extends Taxonomy {

  /**
   * {@inheritdoc]
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('Inconsistent node tagging');
    $this->description = t('Check for taxonomy_index entries pointing to a missing node or term. These should never happen, and should be removed when they do.');
  }

  /**
   * Locate {taxonomy_index} entries linking to a non-existent term or node revision.
   *
   * @return array
   */
  function checkOrphans() {
    $sq = <<<sql
SELECT ti.tid AS titid,ti.nid AS tinid,
  td.tid AS tdtid, td.name AS tdname,
  n.nid
FROM {taxonomy_index} ti
  LEFT JOIN {taxonomy_term_data} td ON ti.tid = td.tid
  LEFT JOIN {node} n ON ti.nid = n.nid
WHERE td.tid IS NULL
  OR n.nid IS NULL
sql;
    // No node access: we are scanning the whole database for a fully privileged user.
    $q = db_query($sq);
    $orphans = array(
      'terms' => array(),
      'nodes' => array(),
    );
    foreach ($q->fetchAll() as $o) {
      if (is_null($o->tdtid)) {
        $orphans['terms'][] = $o->titid;
      }
      if (is_null($o->nid)) {
        $orphans['nodes'][] = $o->tinid;
      }
    }
    $orphans['terms']     = array_unique($orphans['terms']);
    sort($orphans['terms']);
    $orphans['nodes'] = array_unique($orphans['nodes']);
    sort($orphans['nodes']);
    return array(
      'status' => (empty($orphans['terms']) && empty($orphans['nodes'])) ? 1 : 0,
      'result' => $orphans,
    );
  }

  function run() {
    $pass = parent::run();
    $pass->record($this->checkOrphans());
    $pass->life->end();

    // Prepare for theming. Only one pass for this check.
    $result = isset($pass->result[0]) ? $pass->result[0] : NULL;
    $result = array(
      empty($result['terms'])
        ? t('All terms found')
        : t('Missing term IDs: @tids', array('@tids' => implode(', ', $result['terms']))),
      empty($result['nodes'])
        ? t('All nodes found')
        : t('Missing nodes: @nids', array('@nids' => implode(', ', $result['nodes']))),
    );
    $result = theme('item_list', $result);
    $pass->result = $result;
    return $pass;
  }
}
