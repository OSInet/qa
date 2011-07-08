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


CAVEAT: Autoload
----------------

2011-07-07: QA relies on autoload.module to load its various classes, and works 
best with autoload 6.2 branch.

Starting with version dated 2011-07-07, QA can also use autoload 6.x-1.x-dev, 
but autoload will often fail to load classes in various cache-related conditions. 

If this happens, you will need to force autoload to rebuild its class cache, by 
invoking:

  autoload_get_lookup(TRUE);
  
This can be done from the devel/php page or using drush php-eval:

  drush php-eval "autoload_get_lookup(TRUE);"

Using the clear cache choice from admin_menu or devel  will usually NOT be 
sufficient. Upgrading to autoload 6.2 is recommended.
