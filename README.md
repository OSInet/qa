Quality Assurance module
========================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OSInet/qa/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/OSInet/qa/?branch=8.x-1.x)

This module needs to be installed on your site to run checks on various aspects
of your production database, configuration storage (D9/D8) and file layout.

## Controls (checks)

It is designed to be extended by additional modules implementing control plugins.

In D9/D8, these are standard core plugins. In D7/D6, these were derived from
`Drupal\qa\ControlBase`, supported by a custom plugin system.


## Graphs

It also provides a few graphs:

* dependency graph of enabled modules and themes, usable either
on the Web UI for smaller sites or from drush for bigger graphs. This feature
will generate GraphViz `.dot` files, to convert with Graphviz `dot` command,
typically by piping the output like this:

```bash
      $ drush qa-dependencies | dot -Tsvg > qa_dependencies.svg
```

* On Drupal 8.3 and later, graphs of the transitions in a given workflow (for
  core workflows, not for the contrib workflow module). This feature will
  generate GraphViz `.dot` files, to convert with Graphviz `dot` command,
  typically by piping the output like this:

```bash
       $ drush qa_workflow_graph | dot -Tsvg > qa_workflow.svg
```
* The workflow graph also has a tabular version.

## Availability per core version
<table border="1">
  <caption>Porting status on 2020-08-14</caption>
  <tr>
    <th>Package</th>
    <th>Check</th>
    <th>Drupal 9/8</th>
    <th>Drupal 7</th>
    <th>Drupal 6</th>
    </tr>
  <tr>
    <td>Cache</td>
    <td>Size</td>
    <td>Planned</td>
    <td>OK</td>
    <td></td>
    </tr>
  <tr>
    <td>I18N</td>
    <td>Variables</td>
    <td></td>
    <td></td>
    <td>OK</td>
    </tr>
  <tr>
    <td rowspan="2">References</td>
    <td>Integrity</td>
    <td>OK:<ul>
    <li>file</li>
    <li>image</li>
    <li>ER</li>
    <li>ERR</li>
    <li>DER</li>
    </ul></td>
    <td></td>
    <td></td>
    </tr>
  <tr>
    <td>Taxonomy Index</td>
    <td>OK</td>
    <td></td>
    <td>OK</td>
    </tr>
  <tr>
    <td rowspan="3">System</td>
    <td>Dependency (graph)</td>
    <td>OK</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Unused</td>
    <td>OK</td>
    <td>OK</td>
    <td></td>
    </tr>
  <tr>
    <td>Force removed</td>
    <td>?</td>
    <td>OK</td>
    <td></td>
    </tr>
  <tr>
    <td>Taxonomy</td>
    <td>Freetagging</td>
    <td>Planned</td>
    <td></td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Variables</td>
    <td>Size</td>
    <td>Not applicable</td>
    <td>OK</td>
    <td></td>
    </tr>
  <tr>
    <td rowspan="2">Views</td>
    <td>Override</td>
    <td>Not applicable</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td>Php</td>
    <td>Planned</td>
    <td>OK</td>
    <td>OK</td>
    </tr>
  <tr>
    <td rowspan="4">Workflows (Content Moderation)</td>
    <td>Summary</td>
    <td>OK</td>
    <td colspan="2" rowspan="3">Not applicable</td>
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
