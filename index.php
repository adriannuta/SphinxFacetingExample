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

$query = trim($_GET['query']);
$attrs = array(
    'categories',
    'brand_id'
);
$where = array();
foreach ($attrs as $attr) {
    if (isset($_GET[$attr])) {
        $where[] = ' ' . $attr . ' = ' . $_GET[$attr] . ' ';
    }
}
if (isset($_GET['property'])) {
    $query .= ' @property ' . $_GET['property'];
}
if (count($where) > 0) {
    $where = ' AND ' . implode(' AND ', $where);
} else {
    $where = '';
}

$indexes = 'facetdemo';
$stmt = $ln_sph->prepare("SELECT * FROM $indexes WHERE MATCH(:match) $where  LIMIT $start,$offset ");
$stmt->bindValue(':match', $query, PDO::PARAM_STR);
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
foreach ($attrs as $attr) {
    $sql[] = "SELECT *,GROUPBY() as selected,COUNT(*) as cnt,WEIGHT() as w FROM $indexes WHERE MATCH(:match) $where  GROUP BY $attr ORDER BY cnt DESC LIMIT 0,10";
}
$sql = implode('; ', $sql);
$stmt = $ln_sph->prepare($sql);
$stmt->bindValue(':match', $query, PDO::PARAM_STR);
$stmt->execute();
foreach ($attrs as $attr) {
    $rows[$attr] = $stmt->fetchAll();
    $stmt->nextRowset();
}

// string attrs are not yet supported in multi-query optimization, so we run them separate
$stmt = $ln_sph->prepare("SELECT *,COUNT(*) as cnt FROM $indexes WHERE MATCH(:match) $where GROUP BY property ORDER BY cnt DESC  LIMIT 0,10");
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
		 		<label class="radio"> <input type="radio" name="brand_id"
							value="<?=$item['value'];?>"
							<?=(isset($_GET['brand_id']) && ($_GET['brand_id'] == $item['value']))?'checked':'';?>>
			<?='Brand '.$brands[$item['value']-1].' ('.$item['count'].')'?>
		 		</label>
		 	<?php endforeach;?>
					<input type="button" name="reset_brand_id" value="Reset"
							data-target="brand_id" class="btn">
					</div>
				</fieldset>

				<fieldset>
					<legend>Categories</legend>
					<div class="control-group">
		 	<?php foreach($facets['categories'] as $item):?>
		 		<label class="radio"> <input type="radio" name="categories"
							value="<?=$item['value'];?>"
							<?=(isset($_GET['categories']) && ($_GET['categories'] == $item['value']))?'checked':'';?>>
			Category <?=$item['value'].' ('.$item['count'].')'?>
		 		</label>
		 	<?php endforeach;?>
		 		 	                                    <input type="button"
							name="reset_categories" value="Reset" data-target="categories"
							class="btn">
					</div>
				</fieldset>

				<fieldset>
					<legend>Property</legend>
					<div class="control-group">
		 	<?php foreach($facets['property'] as $item):?>
		 		<label class="radio"> <input type="radio" name="property"
							value="<?=$item['value'];?>"
							<?=(isset($_GET['property']) && ($_GET['property'] == $item['value']))?'checked':'';?>>
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
						<li class="active"><a href="index.php">Simple Faceting</a></li>
						<li><a href="facetprices.php">Segmented price ( old style )</a></li>
						<li><a href="facetpricesnew.php">Segmented price ( FACET )</a></li>
						<li><a href="facetmulti.php">Facet with multiple selection</a></li>
					</ul>
					<header>
						<h1>Simple faceting</h1>
					</header>
					<div class="row">
						<div class="span9">
							<p>A multi-query is made on brands (integer) and categories
								(MVA). A separate query is made on string property</p>
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
include 'template/footer.php';
?>
