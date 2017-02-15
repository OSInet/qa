Quality Assurance module
========================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FGM/qa/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/FGM/qa/?branch=8.x-1.x)

This module needs to be installed on your site to run checks on various aspects
of your production database, configuration storage and file layout.

## Controls (checks)

It is designed to be extended by additional module implementing control classes.

In D7/D6, these are derived from `Drupal\qa\ControlBase`, which it will identify automatically.

## Graphs

It also provides a few graphs:

* dependency graph of enabled modules and themes, usable either
on the Web UI for smaller sites or from drush for bigger graphs. This feature
will generate GraphViz `.dot` files, to convert with Graphviz `dot` command,
typically by piping the output like this:

      $ drush qa-dependencies | dot -Tsvg > qa_dependencies.svg 
* On Drupal 8.3 and later, graphs of the transitions in a given workflow (for
  core workflows, not for the contrib workflow module). This feature will 
  generate GraphViz `.dot` files, to convert with Graphviz `dot` command, 
  typically by piping the output like this:

      $ drush qa_workflow_graph | dot -Tsvg > qa_workflow.svg 

## Availability per core version
<table>
  <caption>Status on 2017-02-15</caption>
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
    <td>Not implemented</td>
    <td>OK</td>
    <td>Not implemented</td>
    </tr>
  <tr>
    <td>I18N</td>
    <td>Variables</td>
    <td>Not implemented</td>
    <td>Crashes</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>References</td>
    <td>References</td>
    <td>Not implemented</td>
    <td>Not implemented</td>
    <td>Stub</td>
    </tr>
  <tr>
    <td rowspan="2">System</td>
    <td>Dependency (graph)</td>
    <td>Not implemented</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Unused</td>
    <td>Not implemented</td>
    <td>OK (as page)</td>
    <td>Stub</td>
    </Tr>
  <tr>
    <td rowspan="2">Taxonomy</td>
    <td>Freetagging</td>
    <td>Not implemented</td>
    <td>KO</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Orphans</td>
    <td>Not implemented</td>
    <td>Crashes.</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Variables</td>
    <td>Size</td>
    <td>Not implemented</td>
    <td>OK (as page)</td>
    <td>Not implemented</td>
    </tr>
  <tr>
    <td rowspan="2">Views</td>
    <td>Override</td>
    <td>Not implemented</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Php</td>
    <td>Not implemented</td>
    <td>OK. Improved UI</td>
    <td>OK</td>
    </tr>
  <tr>
    <td rowspan="4">Workflows (Content Moderation)</td>
    <td>Summary</td>
    <td>WIP</td>
    <td rowspan="3">n.a.</td>
    <td rowspan="3">n.a.</td>
    </tr>
  <tr>
    <td>Transition (graph)</td>
    <td>WIP</td>
    </tr>    
  <tr>
    <td>Transition (table)</td>
    <td>WIP</td>
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


