<?php
require __DIR__.'/inc/common.php';
$cid = $_GET['id'] ?? '';
$cats = categories();
if(!isset($cats[$cid])){ header_html('category'); echo '<p>Category not found.</p>'; footer_html(); exit; }
header_html($cats[$cid]['name']);
?>
<h1><?=h($cats[$cid]['name'])?></h1>
<div class="meta"><?=h($cats[$cid]['desc'] ?? '')?></div>
<ul class="clean">
<?php
$threads = get_threads_by_category($cid);
foreach ($threads as $t){
  echo '<li class="row"><a href="/thread.php?c='.h($cid).'&id='.h($t['id']).'">'.h($t['title']).'</a><div class="meta">by <a href="/profile.php?u='.h($t['author']).'">@'.h($t['author']).'</a> • '.date('Y-m-d H:i', (int)($t['created'] ?? now())).'</div></li>';
}
?>
</ul>
<?php footer_html(); ?>
