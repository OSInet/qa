<?php

namespace Drupal\qa\Plugin\Qa\Control\Cache;

use Drupal\qa\Pass;
use Drupal\qa\Plugin\Qa\Control\BaseControl;

/**
 * A QA Plugin for Memcache status over the Memcached (not Memcache) extension.
 *
 * @copyright Copyright (C) 2019 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-7
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 */
class Memcache extends BaseControl {
  const EXTENSION = 'memcached';

  /**
   * The subset of $conf possibly relevant to Memcache.
   *
   * @var array
   */
  const MEMCACHE_KEYS = [
    'cache_backends',
    'cache_class_cache_form',
    'cache_default_class',
    'lock_inc',
    'memcache_bins',
    'memcache_extension',
    'memcache_key_prefix',
    'memcache_servers',
    'memcache_stampede_protection',
    'memcache_stampede_semaphore',
    'memcache_stampede_wait_limit',
    'memcache_stampede_wait_time',
    'page_cache_invoke_hooks',
    'page_cache_without_database',
  ];

  protected $settings;

  /**
   * The Memcache plugin constructor.
   */
  public function __construct() {
    $this->settings = array_intersect_key($GLOBALS['conf'],
      array_combine(self::MEMCACHE_KEYS, self::MEMCACHE_KEYS));

    // Ancestor constructor invokes init() so call it last.
    parent::__construct();
  }

  /**
   * Initializer: must be implemented in concrete classes.
   *
   * Assignment to $this->package_name cannot be factored because it uses a
   * per-class magic constant.
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('Check state of Memcached servers');
    $this->description = t('Validates actual connectivity and current miss and eviction rates. Assumes a correct settings configuration and the memcache module being installed, although not necessarily enabled.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, ['memcache']);
    return $ret;
  }

  /**
   * Check connectivity to a given Drupal cache bin in Memcached.
   *
   * @param string $bin
   *   The bin to check.
   * @param string $cluster
   *   The name of the cluster to which the bin belongs.
   *
   * @return array
   *   A "check" array (name, status, result)
   */
  protected function checkBin(string $bin, string $cluster): array {
    $mc = dmemcache_object($bin);
    if ($mc === FALSE) {
      return [
        'name' => $bin,
        'status' => 0,
        'result' => 'Failed obtaining Memcached object',
      ];
    }

    // Contrary to documentation on
    // https://www.php.net/manual/fr/memcached.getstats.php , this function
    // returns FALSE if it cannot connect, instead of always returning an array.
    // @see https://bugs.php.net/bug.php?id=77809
    /** @var \Memcache|\Memcached $mc */
    $stats = $mc->getStats();
    $resultCode = $mc->getResultCode();
    if ($resultCode !== 0 || !is_array($stats)) {
      return [
        'name' => $bin,
        'status' => 0,
        'result' => t('Error @code: @message', [
          '@code' => $resultCode,
          '@message' => $mc->getResultMessage(),
        ]),
      ];
    }

    return [
      'name' => $bin,
      'status' => 0,
      'result' => "ok",
    ];
  }

  /**
   * Check the configured bins.
   *
   * @param \Drupal\qa\Pass $pass
   *   The current control pass.
   *
   * @return \Drupal\qa\Pass
   *   The updated control pass.
   */
  protected function checkBins(Pass $pass): Pass {
    module_load_include('inc', 'memcache', 'dmemcache');

    foreach ($this->settings['memcache_bins'] as $bin => $cluster) {
      $pass->record($this->checkBin($bin, $cluster));
    }
    $pass->life->end();

    return $pass;
  }

  /**
   * Accumulate and format the results of the control pass.
   *
   * @param \Drupal\qa\Pass $pass
   *   The current control pass.
   *
   * @return \Drupal\qa\Pass
   *   The updated control pass.
   *
   * @throws \Exception
   */
  protected function finalizeBins(Pass $pass): Pass {
    $ok = theme('image', [
      'path' => 'misc/watchdog-ok.png',
      'alt' => t('OK'),
    ]);
    array_walk($pass->result, function (&$res, $bin) use ($ok) {
      if ($res === 'ok') {
        $res = $ok;
      }
      $res = [$bin, $res];
    });
    $result = [
      '#theme' => 'table',
      '#header' => [t('Bin'), t('Status')],
      '#rows' => $pass->result,
    ];
    $pass->result = drupal_render($result);
    return $pass;
  }

  /**
   * Format the results of a pre-control requirements check.
   *
   * @param \Drupal\qa\Pass $pass
   *   The current control pass.
   *
   * @return \Drupal\qa\Pass
   *   The updated control pass.
   */
  protected function finalizeRequirements(Pass $pass): Pass {
    $pass->life->end();
    // Now format result.
    $pass->result = implode($pass->result);
    return $pass;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    if (!extension_loaded(self::EXTENSION)) {
      $pass->record([
        'name' => 'memcached extension available',
        'status' => 0,
        'result' => 'Memcached extension not loaded',
      ]);
      return $this->finalizeRequirements($pass);
    }

    $ext = drupal_strtolower($this->settings['memcache_extension'] ?? self::EXTENSION);
    if ($ext !== self::EXTENSION) {
      $pass->record([
        'name' => 'memcached extension configured',
        'status' => 0,
        'result' => t('@expected extension loaded, but @actual configured instead', [
          '@expected' => drupal_strtolower(self::EXTENSION),
          '@actual' => $ext,
        ]),
      ]);
      return $this->finalizeRequirements($pass);
    }

    $pass = $this->checkBins($pass);
    return $this->finalizeBins($pass);
  }

}
