Quality Assurance module
========================

This module needs to be installed on your site to run checks on various aspects
of your production database and file layout.

It is designed to be extended by additional module implementing control classes
derived from OSInet\DrupalQA\Control, which it will identifiy automatically.

It also provides a dependency graph of enabled modules and themes, usable either
on the Web UI for smaller sites or from drush for bigger graphs. This feature
will only appear on your site if the graphviz_filter module is enabled, which 
implies installation of PEAR Image_Graphviz.

2012-05-19: This Drupal 7 version is not yet feature-equivalent with the D6
version. Porting status below.

<table>
  <tr>
    <th>Package</th>
    <th>Control</th>
    <th>Status</th>
    </tr>
  <tr>
    <td>I18N</td>
    <td>Variables</td>
    <td>Untested.</td>
    </tr>
  <tr>
    <td>References</td>
    <td>References</td>
    <td>Untested. Will test the Rereferences module.</td>
    </tr>
  <tr>
    <td>System</td>
    <td>Unused</td>
    <td>Does not crash but does not work.</td>
    </tr>
  <tr>
    <td>Taxonomy</td>
    <td>Freetagging</td>
    <td>Does not crash but may not work.</td>
    </tr>
  <tr>
    <td>Taxonomy</td>
    <td>Orphans</td>
    <td>Crashes.</td>
    </tr>
  <tr>
    <td>Views</td>
    <td>Override</td>
    <td>Working.</td>
    </tr>
  <tr>
    <td>Views</td>
    <td>Php</td>
    <td>Working. Slight UI improvements over 6.x.</td>
    </tr>
  </table>

Next controls planned:

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


