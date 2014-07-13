Quality Assurance module
========================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FGM/qa/badges/quality-score.png?b=7.x-1.x)](https://scrutinizer-ci.com/g/FGM/qa/?branch=7.x-1.x)

This module needs to be installed on your site to run checks on various aspects
of your production database and file layout.

It is designed to be extended by additional module implementing control classes
derived from OSInet\DrupalQA\Control, which it will identify automatically.

It also provides a dependency graph of enabled modules and themes, usable either
on the Web UI for smaller sites or from drush for bigger graphs. This feature
will only appear on your site if the graphviz_filter module is enabled, which
implies installation of PEAR Image_Graphviz.

<table>
  <caption>Porting status on 2014-07-13</caption>
  <tr>
    <th>Package</th>
    <th>Control</th>
    <th>Drupal 8</th>
    <th>Drupal 7</th>
    <th>Drupal/Pressflow 6</th>
    </tr>
  <tr>
    <td>Cache</td>
    <td>Size</td>
    <td rowspan="8">Branch not opened</td>
    <td>OK</td>
    <td>Not implemented</td>
    </tr>
  <tr>
    <td>I18N</td>
    <td>Variables</td>
    <td>Crashes</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>References</td>
    <td>References</td>
    <td>Not implemented</td>
    <td>Stub</td>
    </tr>
  <tr>
    <td>System</td>
    <td>Unused</td>
    <td>Stub</td>
    <td>Stub</td>
    </tr>
  <tr>
    <td rowspan="2">Taxonomy</td>
    <td>Freetagging</td>
    <td>Runs, but may not work</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Orphans</td>
    <td>Crashes.</td>
    <td>OK</td>
    </tr>
  <tr>
    <td rowspan="2">Views</td>
    <td>Override</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Php</td>
    <td>OK. Improved UI</td>
    <td>OK</td>
    </tr>
  </table>

Next controls envisioned:

<table>
  <tr>
    <th>Package</th>
    <th>Control</th>
    <th>Will check</th>
    </tr>
  <tr>
    <td>EntityReference</td>
    <td>References</td>
    <td>Referential integrity.</td>
    </tr>
  <tr>
    <td>Features</td>
    <td>Storage</td>
    <td>Code / Database / Overridden / Needs Check.</td>
    </tr>
  </table>


