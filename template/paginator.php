<?php $pageCount = ceil($total/$offset);

$_GET['query'] = $query;
?>
<?php if ($pageCount): ?>
<?php 
$start = 0;
if($current >=2) {
    $previous = $current -1;
}
if($current+1<=$pageCount) {
    $next = $current+1;
}
$range = 3;
$first = 0;
$last = $pageCount;
?>
<div class="paginationControl">
<!-- First page link -->
<?php if (isset($previous)): ?>
  <a href="<?echo $url;?>?<?php $_GET['start'] = $first*$offset; echo http_build_query($_GET);?>">
    <i class="icon-step-backward"></i></a> |
<?php else: ?>
  <i class="icon-step-backward disabled"></i></a> |
<?php endif; ?>
<!-- Previous page link -->
<?php if (isset($previous)): ?>
  <a href="<?echo $url;?>?<?php $_GET['start'] = ($previous-1)*$offset; echo http_build_query($_GET);?>">
    <i class="icon-arrow-left"></i></a> |
<?php else: ?>
  <span class="disabled"><i class="icon-arrow-left"></i></span> |
<?php endif; ?>

<!-- Numbered page links -->
<?php for($page = ($current-$range);$page < ($current+$range+1);$page++): ?>
 <?php if ($page > 0 && $page <=$pageCount): ?>
  <?php if ($page != $current): ?>
    <a href="<?echo $url;?>?<?php $_GET['start'] = ($page-1)*$offset; echo http_build_query($_GET);?>">
        <?php echo $page; ?>
    </a> |
  <?php else: ?>
    <?php echo $page; ?> |
  <?php endif; ?>
  <?php endif;?>
<?php endfor; ?>

<!-- Next page link -->
<?php if (isset($next)): ?>
  <a href="?<?echo $url;?><?php $_GET['start'] = ($next-1)*$offset; echo http_build_query($_GET);?>">
   <i class="icon-arrow-right"></i></a>|
<?php else: ?>
  <span class="disabled"><i class="icon-arrow-right"></i></span> |
<?php endif; ?>

<!-- Last page link -->
<?php if (isset($next)): ?>
  <a href="<?echo $url;?>?<?php $_GET['start'] = ($last-1)*$offset; echo http_build_query($_GET);?>">
    <i class="icon-step-forward"></i>
  </a>
<?php else: ?>
 <i class="icon-step-forward disabled"></i>
<?php endif; ?>
</div>
<?php endif; ?>