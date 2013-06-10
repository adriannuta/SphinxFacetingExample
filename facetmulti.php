<?php

require_once 'common.php';
require_once 'functions.php';
$brands = array('One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten');
$docs = array();

$search_query = $query = trim($_GET['query']);
$attrs = array('categories','brand_id','price');

$select = array();
$where = array();

$where_cat = array();
$where_brand =  array();
$where_price = array();
$where_property = array();

if(isset($_GET['categories'])){
	$w = implode(',',$_GET['categories']);
	$where['categories'] = ' categories in ('.$w.') ';
}

if(isset($_GET['brand_id'])){
	$w = implode(',',$_GET['brand_id']);
	$where['brand_id'] = ' brand_id in ('.$w.') ';
}


if(isset($_GET['price'])){
	$w = array();
	foreach($_GET['price'] as $c){
		$w[] = ' (price >= '.($c*200).' AND price <= '.(($c+1)*200-1).') ';
	}
	$w = implode(' OR ',$w);
	$select['price'] = 'IF('.$w.',1,0) as w_p';
	$where['price'] = 'w_p = 1';
}
if(isset($_GET['property'])) {

	$search_query .=' @property '.implode('|',$_GET['property']);
}
if(count($where)>0){
	$where_cat = $where_brand = $where_price = $where_property = $where;
	if(isset($where_cat['categories'])) { unset($where_cat['categories']);  }
	if(count($where_cat)>0){
		$where_cat =  ' AND '.implode(' AND ',$where_cat);
	}else{
		$where_cat = '';
	}

	if(isset($where_brand['brand_id'])) { unset($where_brand['brand_id']);  }
	if(count($where_brand)>0){
		$where_brand =  ' AND '.implode(' AND ',$where_brand);
	}else{
		$where_brand = '';
	}

	if(isset($where_price['price'])) { unset($where_price['price']);}
	if(count($where_price)>0) {
		$where_price =  ' AND '.implode(' AND ',$where_price); 
	}else {
		$where_price = '';
	}
 	
	$where_property =  ' AND '.implode(' AND ',$where_property);
	$where =  ' AND '.implode(' AND ',$where);
}else{
	$where_property = $where_cat = $where_brand = $where_price = $where ='';
}
if(count($select)>0) {
	$select = ','.implode(',',$select);
}else{
	$select = '';
}
$indexes = 'facetdemo';
$stmt = $ln_sph->prepare("SELECT *$select FROM $indexes WHERE MATCH(:match) $where  LIMIT 0,10");
$stmt->bindValue(':match', $search_query,PDO::PARAM_STR);
$stmt->execute();
$docs = $stmt->fetchAll();

$meta = $ln_sph->query("SHOW META LIKE 'total_found'")->fetch();
$total_found = $meta['Value'];

$ln_sph->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);



$sql =array();
$rows = array();


$sql[] = "SELECT *$select,GROUPBY() as selected,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match)
		  $where_cat
		  GROUP BY categories WITHIN GROUP ORDER BY categories ASC ORDER BY categories ASC LIMIT 0,10";
$sql[] = "SELECT *$select,GROUPBY() as selected,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match)  
		  $where_brand
		  GROUP BY brand_id ORDER BY brand_id ASC LIMIT 0,10";

$sql = implode('; ',$sql);
$stmt = $ln_sph->prepare($sql);
$stmt->bindValue(':match', $search_query,PDO::PARAM_STR);
$stmt->execute();
foreach($attrs as $attr){
	$rows[$attr] = $stmt->fetchAll();
	$stmt->nextRowset();
}

//expressions are not yet supported in multi-query optimization,so we run them separate

$stmt = $ln_sph->prepare( "SELECT *,GROUPBY() as selected,COUNT(*) as cnt,(IF(price>=800,4,IF(price>=600,3,IF(price>=400,2,IF(price>=200,1,0))))) as price_seg FROM
		$indexes WHERE MATCH(:match)   $where_price  GROUP BY price_seg ORDER BY price_seg ASC  LIMIT 0,10");
$stmt->bindValue(':match', $search_query,PDO::PARAM_STR);
$stmt->execute();
$prices = $stmt->fetchAll();

// string attrs are not yet supported in multi-query optimization, so we run them separate
$stmt = $ln_sph->prepare("SELECT *$select,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match) $where_property  GROUP BY property ORDER BY property ASC  LIMIT 0,10");
$stmt->bindValue(':match', $query,PDO::PARAM_STR);
$stmt->execute();
$property = $stmt->fetchAll();

$facets = array();
foreach ($property as $p){
	$facets['property'][] = array('value'=>$p['property'],'count'=>$p['cnt']);
}
foreach ($prices as $p){
	$facets['price'][] = array('value'=>$p['price_seg'],'count'=>$p['cnt']);
}
foreach($rows as $k=>$v){
	foreach($v as $x){
		$facets[$k][] = array('value'=>$x['selected'],'count'=>$x['cnt']);
	}
}
?>
<?php
$title = 'Demo simple autocomplete on title';
include 'template/header.php';
?>
<div class="container-fluid">
	<form method="GET" action="" id="search_form">
		<div class="row-fluid">
			<div class="span2">
				<div class="sitebar-nav">
					<ul class="nav nav-list">
						<li><a
							href="">Original
								article</a>
						</li>
						<ul>

							<fieldset>
								<legend>Brands</legend>
								<div class="control-group">
									<?php foreach($facets['brand_id'] as $item):?>
									<label class="checkbox"> <input type="checkbox" name="brand_id[]"
										value="<?=$item['value'];?>"
										<?=(isset($_GET['brand_id']) && (in_array($item['value'],$_GET['brand_id'])))?'checked':'';?>>
										<?='Brand '.$brands[$item['value']-1].' ('.$item['count'].')'?>
									</label>
									<?php endforeach;?>

								</div>
							</fieldset>

							<fieldset>
								<legend>Categories</legend>
								<div class="control-group">
									<?php foreach($facets['categories'] as $item):?>
									<label class="checkbox"> <input type="checkbox"
										name="categories[]" value="<?=$item['value'];?>"
										<?=(isset($_GET['categories']) && (in_array($item['value'],$_GET['categories'])))?'checked':'';?>>
										Category <?=$item['value'].' ('.$item['count'].')'?>
									</label>
									<?php endforeach;?>

								</div>
							</fieldset>

							<fieldset>
								<legend>Price</legend>
								<div class="control-group">
									<?php foreach($facets['price'] as $item):?>
									<label class="checkbox"> <input type="checkbox" name="price[]"
										value="<?=$item['value'];?>"
										<?=(isset($_GET['price']) && (in_array($item['value'],$_GET['price'])))?'checked':'';?>>
										<?=($item['value']*200).'-'.(($item['value']+1)*200).' ('.$item['count'].')'?>
									</label>
									<?php endforeach;?>

								</div>

								<fieldset>
									<legend>Property</legend>
									<div class="control-group">
										<?php foreach($facets['property'] as $item):?>
										<label class="checkbox"> <input type="checkbox"
											name="property[]" value="<?=$item['value'];?>"
											<?=(isset($_GET['property']) && (in_array($item['value'],$_GET['property'])))?'checked':'';?>>
											<?=$item['value'].' ('.$item['count'].')'?>
										</label>
										<?php endforeach;?>

									</div>
								</fieldset>
				
				</div>
			</div>
			<div class="span9">
				<div class="container">
					<ul class="nav nav-pills">
						<ul class="nav nav-pills">
							<li><a href="index.php">Simple Faceting</a></li>
							<li><a href="facetprices.php">Facet with segmented
									price</a></li>
							<li class="active"><a href="facetmulti.php">Facet with multiple
									selection</a></li>
						</ul>
					</ul>
					<header>
						<h1>Facet with segmented price</h1>
					</header>
					<div class="row">
						<div class="span9">
							<p>This is a more advanced example. Multiple selection of a facet is possible. Each facet gets filtering from the other active facets.
							The advantage of this type is that it's possible to see alternatives once a facet is filtered.
							</p>
							<div class="well form-search">
								<input type="text" class="input-large" name="query" id="suggest"
									autocomplete="off" value="<?=$_GET['query'];?>"> <input
									type="submit" class="btn btn-primary" id="send" name="send"
									value="Submit">
								<button type="reset" class="btn " value="Reset">Reset</button>
							</div>
						</div>
					</div>
					<div class="row">
						<?php if (count($docs) > 0): ?>
						<p class="lead">
							Showing first 10 results from a total of
							<?=$total_found?>
							:
						</p>
						<?php foreach ($docs as $doc): ?>
						<div class="span9">
							<div class="container">
								<h3>
									<?= $doc['title'] ?>
								</h3>
								<div class="row-fluid show grid">
									<div class="span3">Categories:
									<?= $doc['categories'] ?></div>
									<div class="span3">Price:
									<?= $doc['price'] ?></div>
									<div class="span3">Property:
									<?= $doc['property'] ?></div>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
						<?php elseif (isset($_GET['query']) && $_GET['query'] != ''): ?>
						<p class="lead">Nothing found!</p>
						<?php endif; ?>
					</div>
	
	</form>
	<?php 
	include 'template/footer_multi.php';
	?>