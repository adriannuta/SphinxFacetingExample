<?php

require_once 'common.php';

$i=0;
$props = array('One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten');
$brands = array('One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten');


for($i=0;$i<10000;$i++) {
	$noc = rand(2, 4);
	$categories = array();
	$cr = rand(9,12);
	
	for($j=1;$j<$noc;$j++) {
		$categories[] = $cr+$j;
	}
	$categories = '('.implode(',',$categories).')';
	$price =  rand(10,1000);
	$brand_id = rand(1,10);
	$brand_name = 'Brand '.$brands[$brand_id-1];
	shuffle($props);
	$property = $props[0];
	
	$title = 'Product '.$props[1].' '.$props[2];
	$stmt = $ln_sph->exec("INSERT INTO facetdemo(id,title,categories,price,brand_id,brand_name,property)
			VALUES($i,'$title',$categories,$price,$brand_id,'$brand_name','$property')
			");
	
}