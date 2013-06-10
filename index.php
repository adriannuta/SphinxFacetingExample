<?php

require_once 'common.php';
require_once 'functions.php';
$brands = array('One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten');
$docs = array();

	$query = trim($_GET['query']);
	$attrs = array('categories','brand_id');
	$where = array();
	foreach($attrs as $attr){
		if(isset($_GET[$attr])){
			$where[] = ' '.$attr.' = '.$_GET[$attr].' ';
		}
	}
	if(isset($_GET['property'])) {
		$query .=' @property '.$_GET['property'];
	}
	if(count($where)>0){
		$where =  ' AND '.implode(' AND ',$where);
	}else{
		$where ='';
	}
	
	$indexes = 'facetdemo';
	$stmt = $ln_sph->prepare("SELECT * FROM $indexes WHERE MATCH(:match) $where  LIMIT 0,10");
	$stmt->bindValue(':match', $query,PDO::PARAM_STR);
	$stmt->execute();
	$docs = $stmt->fetchAll();

	$meta = $ln_sph->query("SHOW META LIKE 'total_found'")->fetch();
	$total_found = $meta['Value'];

	$ln_sph->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
	
	
	
	$sql =array();
	$rows = array();
	foreach($attrs as $attr){
		$sql[] = "SELECT *,GROUPBY() as selected,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match) $where  GROUP BY $attr ORDER BY cnt DESC LIMIT 0,10";
	}
	$sql = implode('; ',$sql);
	$stmt = $ln_sph->prepare($sql);
	$stmt->bindValue(':match', $query,PDO::PARAM_STR);
	$stmt->execute();
	foreach($attrs as $attr){
		$rows[$attr] = $stmt->fetchAll();
		$stmt->nextRowset();
	}

	
	// string attrs are not yet supported in multi-query optimization, so we run them separate
	$stmt = $ln_sph->prepare("SELECT *,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match) $where GROUP BY property ORDER BY cnt DESC  LIMIT 0,10");
	$stmt->bindValue(':match', $query,PDO::PARAM_STR);
	$stmt->execute();
	$property = $stmt->fetchAll();
	
	$facets = array();
	foreach ($property as $p){
		$facets['property'][] = array('value'=>$p['property'],'count'=>$p['cnt']);
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
			<li><a href="">Original article</a>
			</li>
		<ul>
		
		 <fieldset>
    		<legend>Brands</legend>
		 	<div class="control-group">
		 	<?php foreach($facets['brand_id'] as $item):?>
		 		<label class="radio">
		 			<input type="radio" name="brand_id" value="<?=$item['value'];?>" <?=(isset($_GET['brand_id']) && ($_GET['brand_id'] == $item['value']))?'checked':'';?>>
			<?='Brand '.$brands[$item['value']-1].' ('.$item['count'].')'?>
		 		</label>
		 	<?php endforeach;?>
		 		 	
		 	</div>
		 </fieldset>

		<fieldset>
    		<legend>Categories</legend>
		 	<div class="control-group">
		 	<?php foreach($facets['categories'] as $item):?>
		 		<label class="radio">
		 			<input type="radio" name="categories" value="<?=$item['value'];?>" <?=(isset($_GET['categories']) && ($_GET['categories'] == $item['value']))?'checked':'';?>>
			Category <?=$item['value'].' ('.$item['count'].')'?>
		 		</label>
		 	<?php endforeach;?>
		 		 	
		 	</div>
		 </fieldset>
		 
		 <fieldset>
    		<legend>Property</legend>
		 	<div class="control-group">
		 	<?php foreach($facets['property'] as $item):?>
		 		<label class="radio">
		 			<input type="radio" name="property" value="<?=$item['value'];?>" <?=(isset($_GET['property']) && ($_GET['property'] == $item['value']))?'checked':'';?>>
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
		<li class="active"><a href="index.php">Simple Faceting</a></li>
		<li ><a href="facetprices.php">Facet with segmented price</a></li>
		<li ><a href="facetmulti.php">Facet with multiple selection</a></li>
	</ul>
	<header>
		<h1>Simple faceting</h1>
	</header>
	<div class="row">
		<div class="span9">
			<p>A multi-query is made on brands (integer) and categories (MVA). A separate query is name on strin property</p>
			<div class="well form-search">
				<input type="text" class="input-large" name="query" id="suggest" autocomplete="off" value="<?=$_GET['query'];?>"> 
				<input type="submit" class="btn btn-primary" id="send" name="send" value="Submit">
				 <button type="reset"class="btn " value="Reset">Reset</button>
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
	include 'template/footer.php';
	?>