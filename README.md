SphinxFacetingExample
=========================

These samples illustrate faceting using Sphinx search.     
Sphinx querying is made via SphinxQL using PDO driver.    


Requirements :
-------------------------------------------
LAMP  
Sphinx search  
PHP with PDO mysql  

Installation :
-------------------------------------------
Edit `scripts/sphinx.conf` for setting proper paths and db credentials
The example use a single RealTime index which can be generated using `filldb.php` script.     
Start searchd first with
 
    $ searchd -c /path/to/sphinx.conf    
Generate the RT index
 
    $ php filldb.php

License:
-------------------------------------------
Sphinx Samples  is free software, and is released under the terms of the GPL version 2 or (at your option) any later version.

Sphinx website : http://sphinxsearch.com/  
Sphinx read-only repository :https://code.google.com/p/sphinxsearch/ 
