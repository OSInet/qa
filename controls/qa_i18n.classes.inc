<?php
// $Id$
/**
 * @file
 * OSInet QA Plugin for i18n (internationalization) module
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

class QaPackageI18n extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('i18n');
    // $this->description = t('Inconsistent variables translation');
  }
}

/**
 * Find inconsistencies in {i18nvariables} and {languages}
 */
class QaControlI18nVariables extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageI18n';
    $this->title = t('Inconsistent variables translation');
    $this->description = t('In most scenarios, when a variable is translated at least once, it ought to be translated in every language on the site, not more, not less.');
    parent::__construct();
  }

  /**
   * Identify variables translations for languages not currently on site
   */
  function checkExtra() {
    $languages = array_keys(language_list());
    $ph = db_placeholders($languages, 'char');
    $sq = <<<EOT
SELECT v.name, v.language
FROM {i18n_variable} v
WHERE v.language NOT IN ($ph)
ORDER BY 1, 2
EOT;
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
    $sq = <<<EOT
SELECT v.name, v.language
FROM {i18n_variable} v
WHERE v.language IN ($ph)
ORDER BY 1, 2
EOT;
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
