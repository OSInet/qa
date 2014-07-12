<?php

namespace OSInet\DrupalQA\I18n;

use OSInet\DrupalQA\BaseControl;

/**
 * Find inconsistencies in {i18nvariables} and {languages}
 */
class Variables extends BaseControl {

  /**
   * {@inheritdoc]
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('Inconsistent variables translation');
    $this->description = t('In most scenarios, when a variable is translated at least once, it ought to be translated in every language on the site, not more, not less.');
  }

  /**
   * Identify variables translations for languages not currently on site
   */
  function checkExtra() {
    $languages = array_keys(language_list());
    $ph = db_placeholders($languages, 'char');
    $sq = <<<sql
SELECT v.name, v.language
FROM {i18n_variable} v
WHERE v.language NOT IN ($ph)
ORDER BY 1, 2
sql;
    $q = db_query($sq, $languages);
    // No db_rewrite_sql: this needs to be unhindered by any access control
    $vars = array();
    while ($o = db_fetch_object($q)) {
      $vars[$o->name][] = $o->language;
    }

    $items = array();
    foreach ($vars as $name => $var_languages) {
      $items[] = t('@var: @languages', array('@var' => $name, '@languages' => implode(', ', $var_languages)));
    }
    $ret = array(
      'name'   => 'extra',
      'status' => empty($vars) ? 1 : 0,
      'result' => array('extra' => $items)
    );
    return $ret;
  }

  /**
   * Identify variables for which at least one translation is missing
   */
  function checkMissing() {
    $languages = array_keys(language_list());
    $ph = db_placeholders($languages, 'char');
    $sq = <<<sql
SELECT v.name, v.language
FROM {i18n_variable} v
WHERE v.language IN ($ph)
ORDER BY 1, 2
sql;
    $q = db_query($sq, $languages);
    // No db_rewrite_sql: this needs to be unhindered by any access control
    $vars = array();
    while ($o = db_fetch_object($q, $ph)) {
      $vars[$o->name][] = $o->language;
    }

    $items = array();
    foreach ($vars as $name => $var_languages) {
      $missing = array_diff($languages, $var_languages);
      if (!empty($missing)) {
        $items[] = t('@var: @languages', array('@var' => $name, '@languages' => implode(', ', $missing)));
      }
    }

    $ret = array(
      'name'   => 'missing',
      'status' => empty($items) ? 1 : 0,
      'result' => array('missing' => $items),
    );
    return $ret;
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('i18n')); // introduces {i18n_variable}
    return $ret;
  }

  function run() {
    $pass = parent::run();
    $pass->record($this->checkExtra());
    $pass->life->modify();
    $pass->record($this->checkMissing());
    $pass->life->end();

    $extra_row = empty($pass->result['extra']['extra'])
      ? array(array(
          'data' => t('No extra translation found'),
          'colspan' => 2,
          ),
        )
      : array(
          t('Extra translations'),
          theme('item_list', $pass->result['extra']['extra'])
        );

    $missing_row = empty($pass->result['missing']['missing'])
      ? array(array(
          'data' => t('No missing translation found'),
          'colspan' => 2,
          ),
        )
      : array(
          t('Missing translations'),
          theme('item_list', $pass->result['missing']['missing'])
        );

    $rows = array($extra_row, $missing_row);
    $pass->result = theme('table', NULL, $rows);
    return $pass;
  }
}
