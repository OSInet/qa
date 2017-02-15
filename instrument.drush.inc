<?php
/**
 * @file
 * A simple Drush plugin to report on time spent and memory usage
 * without all the noise from the Drush "-d" option. Results look like:

$ drush cc all
'all' cache was cleared.                                                                                                                   [success]
Duration:  3.39 seconds
Memory:
Initial: malloc =  9.01M           real = 10.00M
Final:   malloc =  9.60M (+ 0.59M) real = 11.00M (+ 1.00M)
Peak:    malloc = 10.40M (+ 1.39M) real = 11.00M (+ 1.00M)

 */

/**
 * Implements hook_drush_init().
 */
function instrument_drush_init() {
  global $instrument;
  $instrument = [];
  $instrument['times'] = [microtime(true)];
  $instrument['memory'] = [[
    'current' => [
      'malloc' => memory_get_usage(),
      'true' => memory_get_usage(TRUE),
    ]
  ]];
}

/**
 * Implements hook_drush_exit().
 */
function instrument_drush_exit() {
  global $instrument;
  $instrument['times'][] = microtime(true);
  $instrument['memory'][] = [
    'current' => [
      'malloc' => memory_get_usage(),
      'true' => memory_get_usage(TRUE),
    ],
    'peak' => [
      'malloc' => memory_get_peak_usage(),
      'true' => memory_get_peak_usage(TRUE),
    ]
  ];

  $memory = $instrument['memory'];
  printf("Duration: %5.2f seconds\n", $instrument['times'][1] - $instrument['times'][0]);
  $format = <<<FORMAT
Memory:
  Initial: malloc = %5.2fM           real = %5.2fM
  Final:   malloc = %5.2fM (+%5.2fM) real = %5.2fM (+%5.2fM)
  Peak:    malloc = %5.2fM (+%5.2fM) real = %5.2fM (+%5.2fM)

FORMAT;
  $mega = 2 << 20;
  printf($format,
    $memory[0]['current']['malloc'] / $mega,
    $memory[0]['current']['true'] / $mega,
    $memory[1]['current']['malloc'] / $mega,
    ($memory[1]['current']['malloc'] - $memory[0]['current']['malloc']) / $mega,
    $memory[1]['current']['true'] / $mega,
    ($memory[1]['current']['true'] - $memory[0]['current']['true']) / $mega,
    $memory[1]['peak']['malloc'] / $mega,
    ($memory[1]['peak']['malloc'] - $memory[0]['current']['malloc']) / $mega,
    $memory[1]['peak']['true'] / $mega,
    ($memory[1]['peak']['true'] - $memory[0]['current']['true']) / $mega
  );
}
