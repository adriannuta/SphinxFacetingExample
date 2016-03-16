SphinxFacetingExample
=========================

These samples illustrate faceting using Sphinx search.     
Sphinx querying is made via SphinxQL using PDO driver.    


Requirements :
-------------------------------------------
LAMP  
Sphinx search ( recommended 2.1.1 or higher)  
PHP with PDO mysql  

Please note that the code is made for 2.1.1. The code use LIKE operator on SHOW META.    
For older versions, use only SHOW META, fetch all rows and lookup for the row that has the 'total_found'.  

Installation :
-------------------------------------------
Edit `scripts/sphinx.conf` for setting proper paths and db credentials
The example use a single RealTime index which can be generated using `filldb.php` script.     
Start searchd first with
 
    $ searchd -c /path/to/sphinx.conf    
Generate the RT index
 
    $ php filldb.php

Live demo:
-------------------------------------------
http://demos.sphinxsearch.com/SphinxFacetingExample/  
License:
-------------------------------------------
Sphinx Samples  is free software, and is released under the terms of the GPL version 2 or (at your option) any later version.

Sphinx website : http://sphinxsearch.com/  
Sphinx read-only repository : https://github.com/sphinxsearch/sphinx 
