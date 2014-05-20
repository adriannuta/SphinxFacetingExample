<?php
require_once 'common.php';

$brands = array(
    'One',
    'Two',
    'Three',
    'Four',
    'Five',
    'Six',
    'Seven',
    'Eight',
    'Nine',
    'Ten'
);
$docs = array();
$start = 0;
$offset = 20;
$current = 1;
if (isset($_GET['start'])) {
    $start = $_GET['start'];
    $current = $start / $offset + 1;
}

$search_query = $query = trim($_GET['query']);
$attrs = array(
    'categories',
    'brand_id',
    'price'
);

$select = array();
$where = array();

$where_cat = array();
$where_brand = array();
$where_price = array();
$where_property = array();

if (isset($_GET['categories'])) {
    $w = implode(',', $_GET['categories']);
    $where['categories'] = ' categories in (' . $w . ') ';
}

if (isset($_GET['brand_id'])) {
    $w = implode(',', $_GET['brand_id']);
    $where['brand_id'] = ' brand_id in (' . $w . ') ';
}

if (isset($_GET['price'])) {
    $w = array();
    foreach ($_GET['price'] as $c) {
        $w[] = ' (price >= ' . ($c * 200) . ' AND price <= ' . (($c + 1) * 200 - 1) . ') ';
    }
    $w = implode(' OR ', $w);
    $select['price'] = 'IF(' . $w . ',1,0) as w_p';
    $where['price'] = 'w_p = 1';
}
if (isset($_GET['property'])) {
    
    $search_query .= ' @property ' . implode('|', $_GET['property']);
}
if (count($where) > 0) {
    $where_cat = $where_brand = $where_price = $where_property = $where;
    if (isset($where_cat['categories'])) {
        unset($where_cat['categories']);
    }
    if (count($where_cat) > 0) {
        $where_cat = ' AND ' . implode(' AND ', $where_cat);
    } else {
        $where_cat = '';
    }
    
    if (isset($where_brand['brand_id'])) {
        unset($where_brand['brand_id']);
    }
    if (count($where_brand) > 0) {
        $where_brand = ' AND ' . implode(' AND ', $where_brand);
    } else {
        $where_brand = '';
    }
    
    if (isset($where_price['price'])) {
        unset($where_price['price']);
    }
    if (count($where_price) > 0) {
        $where_price = ' AND ' . implode(' AND ', $where_price);
    } else {
        $where_price = '';
    }
    
    $where_property = ' AND ' . implode(' AND ', $where_property);
    $where = ' AND ' . implode(' AND ', $where);
} else {
    $where_property = $where_cat = $where_brand = $where_price = $where = '';
}
if (count($select) > 0) {
    $select = ',' . implode(',', $select);
} else {
    $select = '';
}
$indexes = 'facetdemo';
$stmt = $ln_sph->prepare("SELECT *$select FROM $indexes WHERE MATCH(:match) $where  LIMIT $start,$offset ");
$stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
$stmt->execute();
$docs = $stmt->fetchAll();

$meta = $ln_sph->query("SHOW META")->fetchAll();
foreach ($meta as $m) {
    $meta_map[$m['Variable_name']] = $m['Value'];
}
$total_found = $meta_map['total_found'];
$total = $meta_map['total'];

$ln_sph->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

$sql = array();
$rows = array();

$sql[] = "SELECT *$select,GROUPBY() as selected,COUNT(DISTINCT categories) as cnt FROM $indexes WHERE MATCH(:match)
		  $where_cat
		  GROUP BY categories  ORDER BY categories ASC LIMIT 0,10";
$sql[] = "SELECT *$select,GROUPBY() as selected,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match)  
		  $where_brand
		  GROUP BY brand_id ORDER BY brand_id ASC LIMIT 0,10";

$sql = implode('; ', $sql);
$stmt = $ln_sph->prepare($sql);
$stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
$stmt->execute();
foreach ($attrs as $attr) {
    $rows[$attr] = $stmt->fetchAll();
    $stmt->nextRowset();
}

// expressions are not yet supported in multi-query optimization,so we run them separate

$stmt = $ln_sph->prepare("SELECT *,GROUPBY() as selected,COUNT(*) as cnt,INTERVAL(price,200,400,600,800) as price_seg FROM
		$indexes WHERE MATCH(:match)   $where_price  GROUP BY price_seg ORDER BY price_seg ASC  LIMIT 0,10");
$stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
$stmt->execute();
$prices = $stmt->fetchAll();

// string attrs are not yet supported in multi-query optimization, so we run them separate
$stmt = $ln_sph->prepare("SELECT *$select,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match) $where_property  GROUP BY property ORDER BY property ASC  LIMIT 0,10");
$stmt->bindValue(':match', $query, PDO::PARAM_STR);
$stmt->execute();
$property = $stmt->fetchAll();

$facets = array();
foreach ($property as $p) {
    $facets['property'][] = array(
        'value' => $p['property'],
        'count' => $p['cnt']
    );
}
foreach ($prices as $p) {
    $facets['price'][] = array(
        'value' => $p['price_seg'],
        'count' => $p['cnt']
    );
}
foreach ($rows as $k => $v) {
    foreach ($v as $x) {
        $facets[$k][] = array(
            'value' => $x['selected'],
            'count' => $x['cnt']
        );
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
				<br> <br> <br> <br>
				<fieldset>
					<legend>Brands</legend>
					<div class="control-group">
									<?php foreach($facets['brand_id'] as $item):?>
									<label class="checkbox"> <input type="checkbox"
							name="brand_id[]" value="<?=$item['value'];?>"
							<?=(isset($_GET['brand_id']) && (in_array($item['value'],$_GET['brand_id'])))?'checked':'';?>>
										<?='Brand '.$brands[$item['value']-1].' ('.$item['count'].')'?>
									</label>
									<?php endforeach;?>
                                    <input type="button"
							name="reset_brand_id" value="Reset" data-target="brand_id"
							class="btn">
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
                                    <input type="button"
							name="reset_categories" value="Reset" data-target="categories"
							class="btn">
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
                                    <input type="button"
							name="reset_price" value="Reset" data-target="price" class="btn">
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
                                        <input type="button"
								name="reset_property" value="Reset" data-target="property"
								class="btn">
						</div>
					</fieldset>
			
			</div>
			<div class="span9">
				<div class="container">
					<ul class="nav nav-pills">
						<ul class="nav nav-pills">
							<li><a href="index.php">Simple Faceting</a></li>
							<li><a href="facetprices.php">Segmented price ( old style )</a></li>
							<li><a href="facetpricesnew.php">Segmented price ( FACET )</a></li>
							<li class="active"><a href="facetmulti.php">Facet with multiple
									selection</a></li>
						</ul>
					</ul>
					<header>
						<h1>Facets with multiple selection</h1>
					</header>
					<div class="row">
						<div class="span9">

							<div class="well form-search">
								<input type="text" class="input-large" name="query" id="suggest"
									autocomplete="off"
									value="<?=isset($_GET['query'])?htmlentities($_GET['query']):''?>">
								<input type="submit" class="btn btn-primary" id="send"
									name="send" value="Submit">
								<button type="reset" class="btn " value="Reset">Reset</button>
							</div>
						</div>
					</div>
					<div class="row">
						<?php if (count($docs) > 0): ?>
							<p class="lead">
							Total found:<?=$total_found?>
						</p>
						<div class="span9"><?php include 'template/paginator.php';?></div>
						<div class="span9">
						  <table class="table">
						  <tr>
						      <th>Title</th>
						      <th>Categories</th>
						      <th>Price</th>
						      <th>Property</th>
						      <th>Brand</th>
						  </tr>
						  <?php foreach ($docs as $doc): ?>
						  <tr>
						      <td><?= $doc['title']?></td>
						      <td><?= $doc['categories'] ?></td>
						      <td><?= $doc['price'] ?></td>
						      <td><?= $doc['property'] ?></td>
						      <td><?= $brands[$doc['brand_id']-1]  ?></td>
						  </tr>
						  <?php endforeach; ?>
						  </table>
						</div>
						<div class="span9"><?php include 'template/paginator.php';?></div>
						<?php elseif (isset($_GET['query']) && $_GET['query'] != ''): ?>
						<p class="lead">Nothing found!</p>
						<?php endif; ?>
					</div>
	
	</form>
	<?php
include 'template/footer_multi.php';
?>
