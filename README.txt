Quality Assurance module
========================

This module needs to be installed on your site to run checks on various aspects
of your production database and file layout.

It is designed to be extended by additional module implementing control classes
derived from QaControl, which it will identifiy automatically.

It also provides a dependency graph of enabled modules and themes, usable either
on the Web UI for smaller sites or from drush for bigger graphs. This feature
will only appear on your site if the graphviz_filter module is enabled, which 
implies installation of PEAR Image_Graphviz.

2012-01-09: This Drupal 7 version is currently only a quick hack to use
the dependencies grapher on D7, not a serious port.