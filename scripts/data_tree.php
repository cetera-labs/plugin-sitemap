<?php

include_once('common_bo.php');
$nodes = \Sitemap\Sitemap::getTreeList($_REQUEST["dirs"], "root", false);

echo json_encode($nodes);

?>
