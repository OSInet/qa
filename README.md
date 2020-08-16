Quality Assurance module
========================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OSInet/qa/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/OSInet/qa/?branch=8.x-1.x)

This module needs to be installed on your site to run checks on various aspects
of your Drupal production database, configuration storage (D9/D8) and file layout.

It also has branches for D7 and D6, each running on the matching version.

_CAVEAT EMPTOR_ - using this module and interpreting its results requires significant
understanding of core operation: just because a check reports on suspicious data
does not _imply_ there is actually an problem, only that human review is needed.
This tools is primarily designed to help professional auditors review sites; it
is not meant for webmasters in the general case.


## Built-in checks

QA is designed to be extended by additional modules implementing control plugins.

| Check                    | Status  | Description |
|--------------------------|---------|-------------|
| Cache.Sizes              | OK      | Report on cache bins with suspicious sizes: empty, too big, too many items, items too large |
| Config.Overrides         | Plan    | Find config not matching its default value. |
| Config.Schema            | Plan    | Find config not matching its schema and schemaless config |
| References.Integrity     | OK      | Find broken references in `file`, `image`, `entity_reference`, `entity_reference_revisions`, and `dynamic_entity_reference` fields |
| References.TaxonomyIndex | OK      | Find broken links in the `taxonomy` relating nodes and taxonomy terms |
| System.External          | OK      | Find code loaded from directories outside the project |
| System.ForceRemoved      | TBP     | Find incorrectly removed extensions    |
| System.UndeclaredDependencies | OK | Find module dependencies not declared in `<module>.info.yml` files. |
| System.Unused            | OK      | Find entirely unused projects          |
| Taxonomy.Freetagging     | TBP     | Find unused free-tagging terms.        |
| Workflows.Summary        | OK      | Summarize content moderation workflows |
| Workflows.Transitions    | WIP     | Find inconsistent transitions          |

- Plan: check is expected to be developed, but not yet started
- TBP: to be ported = check existing in earlier versions but not yet ported to D9/D8.
- WIP: work in progress.

QA also includes some non-check commands.

| Graph               | Status | Description                         |
|---------------------|--------|-------------------------------------|
| `qa:dependencies`   | OK  | Graph of module and theme dependencies |
| `qa:workflows-list` | WIP | Graph of workflow                      |


### Graphs

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


## Copyright and license

This module is &copy; 2005-2020 [Frédéric G. MARAND](https://blog.riff.org/), for [OSInet](https://osinet.fr).

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin
Street, Fifth Floor, Boston, MA  02110-1301, USA.


## Contributing

Developing additional custom checks follows this process:

- create a custom module
- create a `QaCheck` plugin, which will implement `QaCheckInterface`, and likely
  be based on `QaCheckBase` for simplicity. Its properties are:
  - `id` : just like any Drupal plugin. The name should be like `<package>.<check_name>`,
    where `<package>`  will be used to group related packages in the UI. Plugins
    are expected to be located in the `Plugin\QaCheck\<Package>` directory, instead of
    being just in the `Plugin\QaCheck` directory.
  - `label`: the short description of the plugin, used in admin lists.
  - `details`: optional. A longer description of the plugin purpose, used for help in the Web UI. Default: empty.
  - `usesBatch`: optional. Enables use of the Batch API for long-running checks (WIP). Default: `false`.
  - `steps`: optional. The number of different steps reported by the check. Default: 1.
- add a command to `QaCommands`, calling `QaCommands::runPlugin($name)`.

Contributions are welcome: use Github issues and pull requests.

