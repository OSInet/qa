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

Package	        | Control	      | Status
----------------+---------------+----------------------------------------
I18N		          Variables	      Untested.
References  	    References	    Untested. Will test the Rereferences module.
System		        Unused		      Does not crash but does not work.
Taxonomy	        Freetagging	    Does not crash but may not work.
Taxonomy	        Orphans		      Crashes.
Views		          Override	      Working.
Views		          Php		          Working. Slight UI improvements over 6.x.

Next controls planned:

Package         | Control       | Will check
----------------+---------------+----------------------------------------
EntityReference   References      Referential integrity.
Features          Storage         Code / Database / Overridden / Needs Check.

